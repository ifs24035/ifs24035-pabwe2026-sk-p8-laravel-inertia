<?php

namespace Tests\Unit\Repositories;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PostRepository();
    }

    public function test_create_update_and_delete(): void
    {
        $user = User::factory()->create();

        $post = $this->repository->create([
            'user_id' => $user->id,
            'title' => 'Judul',
            'slug' => 'judul',
            'category' => 'Umum',
            'content' => 'Isi artikel',
        ]);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'slug' => 'judul']);

        $this->assertTrue($this->repository->update($post, ['title' => 'Judul Baru']));
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Judul Baru']);

        $this->assertTrue($this->repository->delete($post));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_for_user_applies_filters_and_scope(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Belajar Laravel', 'category' => 'Teknologi', 'content' => 'isi biasa']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Resep Masakan', 'category' => 'Gaya Hidup', 'content' => 'Menggunakan INERTIA di dapur']);
        Post::factory()->published()->create(['user_id' => $other->id, 'title' => 'Artikel Orang Lain']);

        $this->assertEquals(2, $this->repository->forUser($user->id)->total());

        // search: title (case-insensitive) and content
        $this->assertEquals(1, $this->repository->forUser($user->id, 'LARAVEL')->total());
        $this->assertEquals(1, $this->repository->forUser($user->id, 'inertia')->total());
        $this->assertEquals(0, $this->repository->forUser($user->id, 'tidak ada')->total());

        // category
        $this->assertEquals(1, $this->repository->forUser($user->id, null, 'Teknologi')->total());

        // status
        $published = $this->repository->forUser($user->id, null, null, 'published');
        $this->assertEquals(1, $published->total());
        $this->assertEquals('Belajar Laravel', $published->items()[0]->title);

        $draft = $this->repository->forUser($user->id, null, null, 'draft');
        $this->assertEquals(1, $draft->total());
        $this->assertEquals('Resep Masakan', $draft->items()[0]->title);

        // unknown status is ignored
        $this->assertEquals(2, $this->repository->forUser($user->id, null, null, 'whatever')->total());
    }

    public function test_all_for_user_returns_only_own_posts_with_author(): void
    {
        $user = User::factory()->create(['name' => 'Penulis Satu']);
        $other = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);
        Post::factory()->create(['user_id' => $other->id]);

        $posts = $this->repository->allForUser($user->id);

        $this->assertCount(2, $posts);
        $this->assertTrue($posts->first()->relationLoaded('user'));
        $this->assertEquals('Penulis Satu', $posts->first()->user->name);
    }

    public function test_paginate_all_and_all(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $a->id, 'title' => 'Alpha', 'content' => 'konten satu']);
        Post::factory()->draft()->create(['user_id' => $b->id, 'title' => 'Beta', 'content' => 'konten dua']);

        $paginated = $this->repository->paginateAll(null, null, null, 10);
        $this->assertEquals(2, $paginated->total());
        // list queries never load the heavy content column
        $this->assertArrayNotHasKey('content', $paginated->items()[0]->getAttributes());
        $this->assertTrue($paginated->items()[0]->relationLoaded('user'));

        $this->assertEquals(1, $this->repository->paginateAll('alpha')->total());
        $this->assertEquals(1, $this->repository->paginateAll(null, null, 'draft')->total());

        $this->assertCount(2, $this->repository->all());
    }

    public function test_published_paginated_only_returns_published_in_latest_order(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['user_id' => $user->id, 'title' => 'Lama', 'content' => 'konten lama', 'is_published' => true, 'published_at' => now()->subDays(5), 'category' => 'Opini']);
        Post::factory()->create(['user_id' => $user->id, 'title' => 'Baru', 'content' => 'konten baru', 'is_published' => true, 'published_at' => now()->subDay(), 'category' => 'Teknologi']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Draf', 'content' => 'konten draf']);

        $result = $this->repository->publishedPaginated();

        $this->assertEquals(2, $result->total());
        $this->assertEquals('Baru', $result->items()[0]->title);
        $this->assertEquals('Lama', $result->items()[1]->title);

        $this->assertEquals(1, $this->repository->publishedPaginated('lama')->total());
        $this->assertEquals(1, $this->repository->publishedPaginated(null, 'Opini')->total());
        $this->assertEquals(0, $this->repository->publishedPaginated('Draf')->total());
    }

    public function test_find_published_by_slug(): void
    {
        $published = Post::factory()->published()->create(['slug' => 'artikel-terbit']);
        Post::factory()->draft()->create(['slug' => 'artikel-draf']);

        $found = $this->repository->findPublishedBySlugOrFail('artikel-terbit');
        $this->assertEquals($published->id, $found->id);
        $this->assertTrue($found->relationLoaded('user'));

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findPublishedBySlugOrFail('artikel-draf');
    }

    public function test_related_published_prefers_same_category_and_excludes_self(): void
    {
        $user = User::factory()->create();
        $current = Post::factory()->published()->create(['user_id' => $user->id, 'category' => 'Teknologi', 'title' => 'Utama']);
        $sameCategory = Post::factory()->create(['user_id' => $user->id, 'category' => 'Teknologi', 'title' => 'Satu Kategori', 'is_published' => true, 'published_at' => now()->subDays(10)]);
        Post::factory()->create(['user_id' => $user->id, 'category' => 'Opini', 'title' => 'Beda Kategori', 'is_published' => true, 'published_at' => now()->subDay()]);
        Post::factory()->draft()->create(['user_id' => $user->id, 'category' => 'Teknologi', 'title' => 'Draf']);

        $related = $this->repository->relatedPublished($current, 3);

        $this->assertCount(2, $related);
        $this->assertEquals($sameCategory->id, $related->first()->id);
        $this->assertFalse($related->contains('id', $current->id));

        $this->assertCount(1, $this->repository->relatedPublished($current, 1));
    }

    public function test_slug_exists(): void
    {
        Post::factory()->create(['slug' => 'sudah-ada']);

        $this->assertTrue($this->repository->slugExists('sudah-ada'));
        $this->assertFalse($this->repository->slugExists('belum-ada'));
    }

    public function test_get_stats_for_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Post::factory()->published()->count(2)->create(['user_id' => $user->id]);
        Post::factory()->draft()->count(3)->create(['user_id' => $user->id]);
        Post::factory()->published()->create(['user_id' => $other->id]);

        $this->assertSame(
            ['total' => 5, 'published' => 2, 'draft' => 3],
            $this->repository->getStatsForUser($user->id)
        );

        $this->assertSame(
            ['total' => 0, 'published' => 0, 'draft' => 0],
            $this->repository->getStatsForUser(User::factory()->create()->id)
        );
    }
}
