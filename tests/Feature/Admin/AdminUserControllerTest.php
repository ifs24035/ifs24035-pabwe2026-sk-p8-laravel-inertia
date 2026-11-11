<?php

namespace Tests\Feature\Admin;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_forbidden_from_admin_pages(): void
    {
        $member = User::factory()->member()->create();
        $target = User::factory()->member()->create();

        $this->actingAs($member);

        $this->get('/admin/users')->assertStatus(403);
        $this->get("/admin/users/{$target->id}/posts")->assertStatus(403);
        $this->get("/admin/users/{$target->id}/posts/export")->assertStatus(403);
        $this->get('/admin/posts')->assertStatus(403);
        $this->get('/admin/posts/export')->assertStatus(403);
    }

    public function test_admin_can_view_user_list_with_search(): void
    {
        $admin = User::factory()->admin()->create();
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@test.com']);
        User::factory()->create(['name' => 'Citra Lestari', 'email' => 'citra@test.com']);
        Post::factory()->count(2)->create(['user_id' => $budi->id]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserList')
                ->has('users.data', 3)
            );

        $this->actingAs($admin)
            ->get('/admin/users?search=budi')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Budi Santoso')
                ->where('users.data.0.posts_count', 2)
                ->where('filters.search', 'budi')
            );
    }

    public function test_admin_can_view_user_posts_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create(['name' => 'Doni Target']);

        Post::factory()->published()->create(['user_id' => $target->id, 'title' => 'Artikel Spesifik Doni', 'category' => 'Teknologi', 'content' => 'konten a']);
        Post::factory()->draft()->create(['user_id' => $target->id, 'title' => 'Draf Lain Doni', 'category' => 'Opini', 'content' => 'konten b']);
        Post::factory()->create(['title' => 'Milik Orang Lain']);

        $this->actingAs($admin)
            ->get("/admin/users/{$target->id}/posts")
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserPostList')
                ->where('targetUser.name', 'Doni Target')
                ->has('posts.data', 2)
                ->has('categories', 6)
            );

        $this->actingAs($admin)
            ->get("/admin/users/{$target->id}/posts?search=Spesifik")
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Artikel Spesifik Doni')
            );

        $this->actingAs($admin)
            ->get("/admin/users/{$target->id}/posts?status=draft&category=Opini")
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Draf Lain Doni')
                ->where('filters.status', 'draft')
                ->where('filters.category', 'Opini')
            );
    }

    public function test_admin_can_export_user_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create(['name' => 'Budi Target']);
        Post::factory()->count(2)->create(['user_id' => $target->id]);

        $response = $this->actingAs($admin)->get("/admin/users/{$target->id}/posts/export");

        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('artikel-Budi_Target-', (string) $response->headers->get('content-disposition'));
    }

    public function test_admin_can_monitor_all_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $a->id, 'title' => 'Artikel Alpha', 'category' => 'Teknologi', 'content' => 'konten a']);
        Post::factory()->draft()->create(['user_id' => $b->id, 'title' => 'Draf Beta', 'category' => 'Opini', 'content' => 'konten b']);

        $this->actingAs($admin)
            ->get('/admin/posts')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PostList')
                ->has('posts.data', 2)
                ->has('posts.data.0.user.name')
                ->missing('posts.data.0.content')
            );

        $this->actingAs($admin)->get('/admin/posts?search=alpha')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('posts.data.0.title', 'Artikel Alpha'));

        $this->actingAs($admin)->get('/admin/posts?status=draft')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('posts.data.0.title', 'Draf Beta'));

        $this->actingAs($admin)->get('/admin/posts?category=Teknologi')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('filters.category', 'Teknologi'));
    }

    public function test_admin_can_export_all_posts(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/admin/posts/export');

        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('semua-artikel-', (string) $response->headers->get('content-disposition'));
    }
}
