<?php

namespace App\Features\Post\Repositories;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PostRepository implements PostRepositoryInterface
{
    /**
     * Columns used by list views (the heavy "content" column is excluded).
     *
     * @var array<int, string>
     */
    protected const LIST_COLUMNS = [
        'id', 'user_id', 'title', 'slug', 'category', 'excerpt',
        'cover_image_path', 'is_published', 'published_at', 'created_at', 'updated_at',
    ];

    /**
     * Relation constraint for the author (never expose email / password).
     */
    protected const AUTHOR = 'user:id,name,username,profile_photo_path';

    /**
     * {@inheritDoc}
     */
    public function forUser(int $userId, ?string $search = null, ?string $category = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Post::query()->where('user_id', $userId);

        return $this->applyFilters($query, $search, $category, $status)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function allForUser(int $userId): Collection
    {
        return Post::query()
            ->with(self::AUTHOR)
            ->where('user_id', $userId)
            ->latest('id')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function paginateAll(?string $search = null, ?string $category = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Post::query()->select(self::LIST_COLUMNS)->with(self::AUTHOR);

        return $this->applyFilters($query, $search, $category, $status)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function all(): Collection
    {
        return Post::query()
            ->with(self::AUTHOR)
            ->latest('id')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function publishedPaginated(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator
    {
        $query = Post::query()
            ->select(self::LIST_COLUMNS)
            ->with(self::AUTHOR)
            ->where('is_published', true);

        return $this->applyFilters($query, $search, $category, null)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function findPublishedBySlugOrFail(string $slug): Post
    {
        return Post::query()
            ->with(self::AUTHOR)
            ->where('is_published', true)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * {@inheritDoc}
     */
    public function relatedPublished(Post $post, int $limit = 3): Collection
    {
        return Post::query()
            ->select(self::LIST_COLUMNS)
            ->with(self::AUTHOR)
            ->where('is_published', true)
            ->where('id', '!=', $post->id)
            ->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$post->category])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function slugExists(string $slug): bool
    {
        return Post::query()->where('slug', $slug)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $attributes): Post
    {
        return Post::create($attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Post $post, array $attributes): bool
    {
        return $post->update($attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Post $post): bool
    {
        return (bool) $post->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getStatsForUser(int $userId): array
    {
        $total = Post::where('user_id', $userId)->count();
        $published = Post::where('user_id', $userId)->where('is_published', true)->count();

        return [
            'total' => $total,
            'published' => $published,
            'draft' => $total - $published,
        ];
    }

    /**
     * Apply the shared search / category / status filters.
     *
     * Search uses LOWER(...) LIKE so it is case-insensitive on PostgreSQL as well.
     *
     * @param Builder<Post> $query
     * @return Builder<Post>
     */
    protected function applyFilters(Builder $query, ?string $search, ?string $category, ?string $status): Builder
    {
        if (!empty($search)) {
            $term = '%'.mb_strtolower($search).'%';
            $query->where(function (Builder $q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(content) LIKE ?', [$term]);
            });
        }

        if (!empty($category)) {
            $query->where('category', $category);
        }

        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }

        return $query;
    }
}
