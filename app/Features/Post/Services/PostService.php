<?php

namespace App\Features\Post\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        protected PostRepositoryInterface $postRepository
    ) {}

    public function getPostsForUser(int $userId, ?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->postRepository->forUser($userId, $status, $search, $category, $perPage);
    }

    public function getPublishedPosts(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator
    {
        return $this->postRepository->publishedPaginated($search, $category, $perPage);
    }

    public function getPublishedPostBySlug(string $slug): ?Post
    {
        return $this->postRepository->findPublishedBySlug($slug);
    }

    /**
     * @return array{total: int, published: int, draft: int}
     */
    public function getStatsForUser(int $userId): array
    {
        return $this->postRepository->getStatsForUser($userId);
    }

    /**
     * Create a post for the given author. Slug, excerpt and publish time are derived automatically.
     *
     * @param array<string, mixed> $data
     */
    public function createPost(int $userId, array $data): Post
    {
        $isPublished = (bool) ($data['is_published'] ?? false);
        $content = (string) ($data['content'] ?? '');

        return $this->postRepository->create([
            'user_id' => $userId,
            'title' => $data['title'],
            'slug' => $this->generateUniqueSlug((string) $data['title']),
            'category' => PostCategory::fromLoose($data['category'] ?? null)->value,
            'excerpt' => $this->resolveExcerpt($data['excerpt'] ?? null, $content),
            'content' => $content,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
        ]);
    }

    /**
     * Update a post. The slug is regenerated only when the title changes.
     *
     * @param array<string, mixed> $data
     */
    public function updatePost(Post $post, array $data): bool
    {
        $title = (string) ($data['title'] ?? $post->title);
        $content = (string) ($data['content'] ?? $post->content);
        $isPublished = array_key_exists('is_published', $data) ? (bool) $data['is_published'] : $post->is_published;

        return $this->postRepository->update($post, [
            'title' => $title,
            'slug' => $title === $post->title ? $post->slug : $this->generateUniqueSlug($title, $post->id),
            'category' => PostCategory::fromLoose($data['category'] ?? $post->category)->value,
            'excerpt' => $this->resolveExcerpt(array_key_exists('excerpt', $data) ? $data['excerpt'] : $post->excerpt, $content),
            'content' => $content,
            'is_published' => $isPublished,
            'published_at' => $this->resolvePublishedAt($post, $isPublished),
        ]);
    }

    /**
     * Toggle between Draft and Published.
     */
    public function togglePublish(Post $post): bool
    {
        $isPublished = !$post->is_published;

        return $this->postRepository->update($post, [
            'is_published' => $isPublished,
            'published_at' => $this->resolvePublishedAt($post, $isPublished),
        ]);
    }

    public function deletePost(Post $post): bool
    {
        $this->removeStoredCover($post);

        return $this->postRepository->delete($post);
    }

    public function updateCoverPhoto(Post $post, UploadedFile $file): string
    {
        $this->removeStoredCover($post);

        $path = $file->store('post-covers', 'public');
        $this->postRepository->update($post, ['cover_image_path' => $path]);

        return $path;
    }

    public function deleteCoverPhoto(Post $post): bool
    {
        $this->removeStoredCover($post);

        return $this->postRepository->update($post, ['cover_image_path' => null]);
    }

    /**
     * Build a URL-friendly slug that is unique in the posts table.
     *
     * @param list<string> $reserved Slugs already taken in the current batch (e.g. Excel import).
     */
    public function generateUniqueSlug(string $title, ?int $ignoreId = null, array $reserved = []): string
    {
        $base = Str::slug($title) ?: 'artikel';
        $base = Str::limit($base, 200, '');
        $slug = $base;
        $counter = 2;

        while (in_array($slug, $reserved, true) || $this->postRepository->slugExists($slug, $ignoreId)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Use the given excerpt, or build one from the first ~160 characters of the content.
     */
    public function resolveExcerpt(mixed $excerpt, string $content): ?string
    {
        $excerpt = trim((string) $excerpt);
        if ($excerpt !== '') {
            return $excerpt;
        }

        $plain = trim((string) preg_replace('/\s+/', ' ', preg_replace('/[#*_>`\[\]()!-]/', '', $content)));

        return $plain === '' ? null : Str::limit($plain, 160);
    }

    protected function resolvePublishedAt(Post $post, bool $isPublished): mixed
    {
        if (!$isPublished) {
            return null;
        }

        return $post->published_at ?? now();
    }

    protected function removeStoredCover(Post $post): void
    {
        if ($post->cover_image_path && Storage::disk('public')->exists($post->cover_image_path)) {
            Storage::disk('public')->delete($post->cover_image_path);
        }
    }
}
