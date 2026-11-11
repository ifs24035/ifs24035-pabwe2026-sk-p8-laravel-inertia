<?php

namespace Tests\Unit\Services;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PostService(new PostRepository());
        Storage::fake('public');
    }

    public function test_create_draft_post_generates_slug_and_excerpt(): void
    {
        $user = User::factory()->create();

        $post = $this->service->createPost($user->id, [
            'title' => 'Belajar Laravel 13',
            'category' => 'Teknologi',
            'content' => "## Pendahuluan\n\nIni adalah **isi** artikel.",
        ]);

        $this->assertEquals('belajar-laravel-13', $post->slug);
        $this->assertEquals('Teknologi', $post->category);
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
        $this->assertEquals('Pendahuluan Ini adalah isi artikel.', $post->excerpt);
    }

    public function test_create_published_post_sets_published_at_and_defaults_category(): void
    {
        $user = User::factory()->create();

        $post = $this->service->createPost($user->id, [
            'title' => 'Artikel Terbit',
            'excerpt' => 'Ringkasan manual',
            'content' => 'Isi',
            'is_published' => true,
        ]);

        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
        $this->assertEquals('Umum', $post->category);
        $this->assertEquals('Ringkasan manual', $post->excerpt);
    }

    public function test_update_post_keeps_slug_and_handles_publication(): void
    {
        $post = Post::factory()->draft()->create(['title' => 'Judul Lama', 'slug' => 'judul-lama', 'category' => 'Umum', 'excerpt' => 'Ringkasan lama']);

        // Title/content only: excerpt, category and publication are untouched
        $this->service->updatePost($post, ['title' => 'Judul Baru', 'content' => 'Konten baru']);
        $post->refresh();
        $this->assertEquals('Judul Baru', $post->title);
        $this->assertEquals('judul-lama', $post->slug);
        $this->assertEquals('Konten baru', $post->content);
        $this->assertEquals('Ringkasan lama', $post->excerpt);
        $this->assertEquals('Umum', $post->category);
        $this->assertFalse($post->is_published);

        // Publish with new excerpt & category
        $this->service->updatePost($post, ['category' => 'Opini', 'excerpt' => 'Ringkasan baru', 'is_published' => true]);
        $post->refresh();
        $this->assertEquals('Opini', $post->category);
        $this->assertEquals('Ringkasan baru', $post->excerpt);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);

        // Re-saving as published keeps the original publication time
        $firstPublishedAt = $post->published_at->toDateTimeString();
        $this->travel(2)->hours();
        $this->service->updatePost($post, ['is_published' => true]);
        $this->assertEquals($firstPublishedAt, $post->fresh()->published_at->toDateTimeString());

        // Blank excerpt is regenerated from content; unpublish clears published_at
        $this->service->updatePost($post, ['excerpt' => '  ', 'is_published' => false]);
        $post->refresh();
        $this->assertEquals('Konten baru', $post->excerpt);
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
    }

    public function test_toggle_publish(): void
    {
        $post = Post::factory()->draft()->create();

        $this->assertTrue($this->service->togglePublish($post));
        $post->refresh();
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);

        $this->assertTrue($this->service->togglePublish($post));
        $post->refresh();
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
    }

    public function test_update_and_delete_cover_image(): void
    {
        $post = Post::factory()->create();

        $firstPath = $this->service->updateCoverImage($post, UploadedFile::fake()->image('a.jpg', 600, 400));
        Storage::disk('public')->assertExists($firstPath);
        $this->assertEquals($firstPath, $post->fresh()->cover_image_path);

        // Replacing deletes the previous file
        $secondPath = $this->service->updateCoverImage($post->fresh(), UploadedFile::fake()->image('b.jpg', 600, 400));
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->assertTrue($this->service->deleteCoverImage($post->fresh()));
        Storage::disk('public')->assertMissing($secondPath);
        $this->assertNull($post->fresh()->cover_image_path);

        // Deleting when there is no cover is harmless
        $this->assertTrue($this->service->deleteCoverImage($post->fresh()));
    }

    public function test_delete_post_removes_cover_file(): void
    {
        $post = Post::factory()->create();
        $path = $this->service->updateCoverImage($post, UploadedFile::fake()->image('c.jpg'));

        $this->assertTrue($this->service->deletePost($post->fresh()));

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_generate_unique_slug(): void
    {
        $this->assertEquals('judul-yang-sama', $this->service->generateUniqueSlug('Judul yang Sama'));

        Post::factory()->create(['slug' => 'judul-yang-sama']);
        $this->assertEquals('judul-yang-sama-2', $this->service->generateUniqueSlug('Judul yang Sama'));

        Post::factory()->create(['slug' => 'judul-yang-sama-2']);
        $this->assertEquals('judul-yang-sama-3', $this->service->generateUniqueSlug('Judul yang Sama'));

        // Titles without any slug-able character fall back to "artikel"
        $this->assertEquals('artikel', $this->service->generateUniqueSlug('!!!'));
    }

    public function test_resolve_excerpt(): void
    {
        $this->assertEquals('Manual', $this->service->resolveExcerpt('  Manual  ', 'Isi'));
        $this->assertEquals('Isi artikel', $this->service->resolveExcerpt(null, 'Isi artikel'));
        $this->assertEquals('Isi artikel', $this->service->resolveExcerpt('', '<b>Isi</b> `artikel`'));

        $long = $this->service->resolveExcerpt(null, str_repeat('kata ', 100));
        $this->assertLessThanOrEqual(163, mb_strlen($long));
        $this->assertStringEndsWith('...', $long);
    }

    public function test_query_wrappers_delegate_to_repository(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $published = Post::factory()->published()->create(['user_id' => $user->id, 'category' => 'Teknologi', 'slug' => 'terbit']);
        Post::factory()->draft()->create(['user_id' => $user->id]);
        Post::factory()->published()->create(['user_id' => $other->id, 'category' => 'Teknologi']);

        $this->assertEquals(2, $this->service->getPostsForUser($user->id)->total());
        $this->assertSame(['total' => 2, 'published' => 1, 'draft' => 1], $this->service->getStatsForUser($user->id));
        $this->assertCount(2, $this->service->getAllPostsForUser($user->id));
        $this->assertEquals(3, $this->service->getAllPosts()->total());
        $this->assertCount(3, $this->service->getEveryPost());
        $this->assertEquals(2, $this->service->getPublicPosts()->total());
        $this->assertEquals($published->id, $this->service->getPublicPost('terbit')->id);
        $this->assertCount(1, $this->service->getRelatedPosts($published));
    }
}
