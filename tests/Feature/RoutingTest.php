<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_renders_public_gallery_for_guest(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->has('posts')
                ->has('categories', 6)
            );
    }

    public function test_root_renders_public_gallery_for_member(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get('/')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->where('auth.user.id', $member->id)
            );
    }

    public function test_articles_route_renders_public_gallery(): void
    {
        $this->get('/articles')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Public/PostIndex'));
    }

    public function test_guest_is_redirected_from_dashboard_and_admin_routes(): void
    {
        $this->get('/dashboard/posts')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
        $this->get('/admin/posts')->assertRedirect(route('login'));
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_is_redirected_away_from_guest_pages(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->get('/login')->assertRedirect();
        $this->actingAs($member)->get('/register')->assertRedirect();
    }

    public function test_health_check_endpoint(): void
    {
        $this->get('/up')
            ->assertStatus(200);
    }
}
