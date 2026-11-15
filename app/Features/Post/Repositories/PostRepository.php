<?php

namespace App\Features\Post\Repositories;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PostRepository implements PostRepositoryInterface
{
    public function forUser(int $userId, ?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Post::query()->where('user_id', $userId);

        return $this->applyFilters($query, $status, $search, $category)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allPaginated(?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Post::query()->with('user:id,name,username');

        return $this->applyFilters($query, $status, $search, $category)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function publishedPaginated(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator
    {
        $query = Post::query()->with('user:id,name,username,profile_photo_path');

        return $this->applyFilters($query, 'published', $search, $category)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findPublishedBySlug(string $slug): ?Post
    {
        return Post::query()
            ->with('user:id,name,username,profile_photo_path')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();
    }

    public function allForUser(int $userId): Collection
    {
        return Post::query()->with('user:id,name,username')->where('user_id', $userId)->latest('id')->get();
    }

    public function allWithAuthor(): Collection
    {
        return Post::query()->with('user:id,name,username')->latest('id')->get();
    }

    public function create(array $attributes): Post
    {
        return Post::create($attributes);
    }

    public function update(Post $post, array $attributes): bool
    {
        return $post->update($attributes);
    }

    public function delete(Post $post): bool
    {
        return (bool) $post->delete();
    }

    public function insertMany(array $records): bool
    {
        if (empty($records)) {
            return true;
        }

        return Post::insert($records);
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Post::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function getStatsForUser(int $userId): array
    {
        $row = Post::query()
            ->where('user_id', $userId)
            ->selectRaw('COUNT(*) as total')
            // "CASE WHEN is_published" works for PostgreSQL boolean and SQLite integer columns.
            ->selectRaw('SUM(CASE WHEN is_published THEN 1 ELSE 0 END) as published')
            ->first();

        $total = (int) $row->total;
        $published = (int) $row->published;

        return [
            'total' => $total,
            'published' => $published,
            'draft' => $total - $published,
        ];
    }

    /**
     * Apply the shared status / search / category filters.
     * Search uses LOWER(...) LIKE so it is case-insensitive on PostgreSQL and SQLite alike.
     *
     * @param Builder<Post> $query
     * @return Builder<Post>
     */
    protected function applyFilters(Builder $query, ?string $status, ?string $search, ?string $category): Builder
    {
        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }

        if (!empty($category)) {
            $query->where('category', $category);
        }

        if (!empty($search)) {
            $needle = '%'.mb_strtolower($search).'%';
            $query->where(function (Builder $q) use ($needle) {
                $q->whereRaw('LOWER(title) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(content) LIKE ?', [$needle]);
            });
        }

        return $query;
    }
}
