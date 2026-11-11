<?php

namespace Tests\Unit\Models;

use App\Features\Post\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($user->id, $post->user->id);
    }

    public function test_casts(): void
    {
        $post = Post::factory()->published()->create();
        $post->refresh();

        $this->assertTrue($post->is_published);
        $this->assertInstanceOf(Carbon::class, $post->published_at);

        $draft = Post::factory()->draft()->create();
        $draft->refresh();

        $this->assertFalse($draft->is_published);
        $this->assertNull($draft->published_at);
    }

    public function test_cover_image_url(): void
    {
        Storage::fake('public');

        $withoutCover = Post::factory()->create(['cover_image_path' => null]);
        $this->assertNull($withoutCover->coverImageUrl());

        $missingFile = Post::factory()->create(['cover_image_path' => 'post-covers/missing.jpg']);
        $this->assertNull($missingFile->coverImageUrl());

        Storage::disk('public')->put('post-covers/cover.jpg', 'content');
        $withCover = Post::factory()->create(['cover_image_path' => 'post-covers/cover.jpg']);
        $this->assertStringContainsString('cover.jpg', (string) $withCover->coverImageUrl());
    }

    public function test_slug_must_be_unique(): void
    {
        Post::factory()->create(['slug' => 'slug-sama']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Post::factory()->create(['slug' => 'slug-sama']);
    }

    public function test_factory_states(): void
    {
        $this->assertTrue(Post::factory()->published()->make()->is_published);
        $this->assertFalse(Post::factory()->draft()->make()->is_published);
    }
}
