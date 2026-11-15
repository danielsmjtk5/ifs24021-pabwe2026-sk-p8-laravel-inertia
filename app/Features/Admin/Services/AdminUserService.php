<?php

namespace App\Features\Admin\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminUserService
{
    public function __construct(
        protected PostRepositoryInterface $postRepository
    ) {}

    /**
     * Paginated users with their total and published post counts (still one query).
     */
    public function getUsersList(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = User::query()
            ->withCount([
                'posts',
                'posts as published_posts_count' => fn (Builder $q) => $q->where('is_published', true),
            ]);

        if (!empty($search)) {
            $needle = '%'.mb_strtolower($search).'%';
            $query->where(function (Builder $q) use ($needle) {
                $q->whereRaw('LOWER(name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$needle]);
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Read-only list of posts written by one user.
     */
    public function getUserPosts(int $userId, ?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->postRepository->forUser($userId, $status, $search, $category, $perPage);
    }
}
