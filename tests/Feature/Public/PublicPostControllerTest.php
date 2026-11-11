<?php

namespace Tests\Feature\Public;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_published_posts(): void
    {
        $author = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $author->id, 'title' => 'Artikel Publik']);
        Post::factory()->draft()->create(['user_id' => $author->id, 'title' => 'Draf Rahasia']);

        $this->get('/')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Artikel Publik')
                ->where('posts.data.0.user.id', $author->id)
                ->missing('posts.data.0.user.email')
                ->missing('posts.data.0.content')
            );
    }

    public function test_articles_index_search_and_category_filter(): void
    {
        $author = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $author->id, 'title' => 'Belajar Laravel', 'category' => 'Teknologi', 'content' => 'konten satu']);
        Post::factory()->published()->create(['user_id' => $author->id, 'title' => 'Resep Nasi Goreng', 'category' => 'Gaya Hidup', 'content' => 'Tambahkan LARAVEL daun bawang']);
        Post::factory()->published()->create(['user_id' => $author->id, 'title' => 'Opini Harian', 'category' => 'Opini', 'content' => 'konten tiga']);

        // Title or content, case-insensitive
        $this->get('/articles?search=laravel')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 2)
                ->where('filters.search', 'laravel')
            );

        $this->get('/articles?category=Opini')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Opini Harian')
                ->where('filters.category', 'Opini')
            );

        $this->get('/articles?search=laravel&category=Teknologi')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Belajar Laravel')
            );

        $this->get('/articles?search=tidak-ada-sama-sekali')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));
    }

    public function test_gallery_is_paginated_by_nine(): void
    {
        $author = User::factory()->create();
        Post::factory()->published()->count(11)->create(['user_id' => $author->id]);

        $this->get('/articles')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 9)
                ->where('posts.total', 11)
                ->where('posts.last_page', 2)
            );

        $this->get('/articles?page=2')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
    }

    public function test_guest_can_read_published_article_by_slug(): void
    {
        $author = User::factory()->create(['name' => 'Penulis Hebat', 'username' => 'hebat']);
        $post = Post::factory()->published()->create([
            'user_id' => $author->id,
            'title' => 'Judul Artikel',
            'slug' => 'judul-artikel',
            'category' => 'Teknologi',
            'content' => '## Isi Markdown',
        ]);
        Post::factory()->published()->create(['user_id' => $author->id, 'category' => 'Teknologi']);
        Post::factory()->published()->create(['user_id' => $author->id, 'category' => 'Opini']);
        Post::factory()->draft()->create(['user_id' => $author->id, 'category' => 'Teknologi']);

        $this->get('/articles/judul-artikel')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostShow')
                ->where('post.id', $post->id)
                ->where('post.title', 'Judul Artikel')
                ->where('post.content', '## Isi Markdown')
                ->where('post.author.name', 'Penulis Hebat')
                ->where('post.author.username', 'hebat')
                ->has('post.published_at')
                ->has('related', 2)
                ->has('categories', 6)
            );
    }

    public function test_published_at_may_be_null_for_legacy_rows(): void
    {
        $post = Post::factory()->create(['is_published' => true, 'published_at' => null, 'slug' => 'tanpa-tanggal']);

        $this->get('/articles/tanpa-tanggal')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.id', $post->id)
                ->where('post.published_at', null)
            );
    }

    public function test_draft_or_unknown_slug_returns_404(): void
    {
        Post::factory()->draft()->create(['slug' => 'masih-draf']);

        $this->get('/articles/masih-draf')->assertStatus(404);
        $this->get('/articles/tidak-ada')->assertStatus(404);
    }

    public function test_public_pages_are_reachable_when_logged_in(): void
    {
        $member = User::factory()->member()->create();
        Post::factory()->published()->create(['slug' => 'artikel-a']);

        $this->actingAs($member)->get('/articles/artikel-a')->assertStatus(200);
        $this->actingAs($member)->get('/articles')->assertStatus(200);
    }
}
