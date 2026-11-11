<?php

namespace App\Features\Post\Contracts;

use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PostRepositoryInterface
{
    /**
     * Get paginated posts of an author with optional search, category and status filters.
     * Status: "published" | "draft".
     */
    public function forUser(int $userId, ?string $search = null, ?string $category = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get all posts of an author without pagination.
     *
     * @return Collection<int, Post>
     */
    public function allForUser(int $userId): Collection;

    /**
     * Get paginated posts of every author (admin monitoring).
     */
    public function paginateAll(?string $search = null, ?string $category = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get every post in the system without pagination (admin export).
     *
     * @return Collection<int, Post>
     */
    public function all(): Collection;

    /**
     * Get paginated published posts for the public gallery.
     */
    public function publishedPaginated(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator;

    /**
     * Find a published post by slug or throw a ModelNotFoundException.
     */
    public function findPublishedBySlugOrFail(string $slug): Post;

    /**
     * Get other published posts, preferring the same category.
     *
     * @return Collection<int, Post>
     */
    public function relatedPublished(Post $post, int $limit = 3): Collection;

    /**
     * Determine whether a slug is already used.
     */
    public function slugExists(string $slug): bool;

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
     * Get statistics summary for an author.
     *
     * @return array{total: int, published: int, draft: int}
     */
    public function getStatsForUser(int $userId): array;
}
