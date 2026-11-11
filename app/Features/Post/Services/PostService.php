<?php

namespace App\Features\Post\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        protected PostRepositoryInterface $postRepository
    ) {}

    /**
     * Get paginated posts of an author.
     */
    public function getPostsForUser(int $userId, ?string $search = null, ?string $category = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->postRepository->forUser($userId, $search, $category, $status, $perPage);
    }

    /**
     * Get statistics summary of an author.
     *
     * @return array{total: int, published: int, draft: int}
     */
    public function getStatsForUser(int $userId): array
    {
        return $this->postRepository->getStatsForUser($userId);
    }

    /**
     * Get all posts of an author without pagination (used for export).
     *
     * @return Collection<int, Post>
     */
    public function getAllPostsForUser(int $userId): Collection
    {
        return $this->postRepository->allForUser($userId);
    }

    /**
     * Get paginated posts of every author (admin monitoring).
     */
    public function getAllPosts(?string $search = null, ?string $category = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->postRepository->paginateAll($search, $category, $status, $perPage);
    }

    /**
     * Get every post in the system (admin export).
     *
     * @return Collection<int, Post>
     */
    public function getEveryPost(): Collection
    {
        return $this->postRepository->all();
    }

    /**
     * Get paginated published posts for the public gallery.
     */
    public function getPublicPosts(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator
    {
        return $this->postRepository->publishedPaginated($search, $category, $perPage);
    }

    /**
     * Get a published post by slug or fail with 404.
     */
    public function getPublicPost(string $slug): Post
    {
        return $this->postRepository->findPublishedBySlugOrFail($slug);
    }

    /**
     * Get related published posts.
     *
     * @return Collection<int, Post>
     */
    public function getRelatedPosts(Post $post, int $limit = 3): Collection
    {
        return $this->postRepository->relatedPublished($post, $limit);
    }

    /**
     * Create a new post for the author.
     *
     * @param array<string, mixed> $data
     */
    public function createPost(int $userId, array $data): Post
    {
        $isPublished = (bool) ($data['is_published'] ?? false);

        return $this->postRepository->create([
            'user_id' => $userId,
            'title' => $data['title'],
            'slug' => $this->generateUniqueSlug($data['title']),
            'category' => $data['category'] ?? PostCategory::UMUM->value,
            'excerpt' => $this->resolveExcerpt($data['excerpt'] ?? null, $data['content']),
            'content' => $data['content'],
            'cover_image_path' => null,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
        ]);
    }

    /**
     * Update an existing post. The slug stays stable so public URLs do not break.
     *
     * @param array<string, mixed> $data
     */
    public function updatePost(Post $post, array $data): bool
    {
        $content = $data['content'] ?? $post->content;

        $attributes = [
            'title' => $data['title'] ?? $post->title,
            'category' => $data['category'] ?? $post->category,
            'content' => $content,
            'excerpt' => array_key_exists('excerpt', $data)
                ? $this->resolveExcerpt($data['excerpt'], $content)
                : $post->excerpt,
        ];

        if (array_key_exists('is_published', $data)) {
            $isPublished = (bool) $data['is_published'];
            $attributes['is_published'] = $isPublished;
            $attributes['published_at'] = $isPublished ? ($post->published_at ?? now()) : null;
        }

        return $this->postRepository->update($post, $attributes);
    }

    /**
     * Toggle publication state between published and draft.
     */
    public function togglePublish(Post $post): bool
    {
        $publish = !$post->is_published;

        return $this->postRepository->update($post, [
            'is_published' => $publish,
            'published_at' => $publish ? now() : null,
        ]);
    }

    /**
     * Upload or replace the cover image of a post.
     */
    public function updateCoverImage(Post $post, mixed $file): string
    {
        $this->removeCoverFile($post);

        $path = $file->store('post-covers', 'public');
        $this->postRepository->update($post, ['cover_image_path' => $path]);

        return $path;
    }

    /**
     * Delete the cover image of a post.
     */
    public function deleteCoverImage(Post $post): bool
    {
        $this->removeCoverFile($post);

        return $this->postRepository->update($post, ['cover_image_path' => null]);
    }

    /**
     * Delete a post together with its cover image.
     */
    public function deletePost(Post $post): bool
    {
        $this->removeCoverFile($post);

        return $this->postRepository->delete($post);
    }

    /**
     * Generate a unique URL slug from a title (suffix -2, -3, ... when taken).
     */
    public function generateUniqueSlug(string $title): string
    {
        $base = Str::slug(mb_substr($title, 0, 240));
        if ($base === '') {
            $base = 'artikel';
        }

        $slug = $base;
        $counter = 2;
        while ($this->postRepository->slugExists($slug)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Use the given excerpt, or build a short plain-text one from the markdown content.
     */
    public function resolveExcerpt(?string $excerpt, string $content): string
    {
        $excerpt = trim((string) $excerpt);
        if ($excerpt !== '') {
            return $excerpt;
        }

        $plain = (string) preg_replace('/[#*_`>~\[\]()!|-]+/', ' ', strip_tags($content));
        $plain = trim((string) preg_replace('/\s+/', ' ', $plain));

        return Str::limit($plain, 160);
    }

    /**
     * Remove the cover file from the public disk if it exists.
     */
    protected function removeCoverFile(Post $post): void
    {
        if ($post->cover_image_path && Storage::disk('public')->exists($post->cover_image_path)) {
            Storage::disk('public')->delete($post->cover_image_path);
        }
    }
}
