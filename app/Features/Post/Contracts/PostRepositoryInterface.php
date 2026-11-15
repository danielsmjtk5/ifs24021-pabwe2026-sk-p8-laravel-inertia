<?php

namespace App\Features\Post\Contracts;

use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PostRepositoryInterface
{
    /**
     * Paginated posts of one author, filtered by status (published|draft), search and category.
     */
    public function forUser(int $userId, ?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * Paginated posts of all authors (admin monitoring), with the author eager loaded.
     */
    public function allPaginated(?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * Paginated published posts for the public gallery.
     */
    public function publishedPaginated(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator;

    /**
     * Find one published post by slug, or null.
     */
    public function findPublishedBySlug(string $slug): ?Post;

    /**
     * All posts of one author (for Excel export).
     *
     * @return Collection<int, Post>
     */
    public function allForUser(int $userId): Collection;

    /**
     * All posts in the system with their author (for Excel export).
     *
     * @return Collection<int, Post>
     */
    public function allWithAuthor(): Collection;

    /**
     * Create a new post.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Post;

    /**
     * Update an existing post.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(Post $post, array $attributes): bool;

    /**
     * Delete an existing post.
     */
    public function delete(Post $post): bool;

    /**
     * Insert many posts with a single query.
     *
     * @param array<int, array<string, mixed>> $records
     */
    public function insertMany(array $records): bool;

    /**
     * Check whether a slug is already used (optionally ignoring one post id).
     */
    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    /**
     * Statistic summary of one author.
     *
     * @return array{total: int, published: int, draft: int}
     */
    public function getStatsForUser(int $userId): array;
}
