<?php

namespace Tests\Unit\Policies;

use App\Features\Post\Models\Post;
use App\Features\Post\Policies\PostPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected PostPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PostPolicy();
    }

    public function test_any_user_can_view_any_and_create(): void
    {
        $user = User::factory()->member()->create();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->create($user));
    }

    public function test_owner_can_view_update_and_delete(): void
    {
        $owner = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->view($owner, $post));
        $this->assertTrue($this->policy->update($owner, $post));
        $this->assertTrue($this->policy->delete($owner, $post));
    }

    public function test_other_member_cannot_access_the_post(): void
    {
        $owner = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($this->policy->view($other, $post));
        $this->assertFalse($this->policy->update($other, $post));
        $this->assertFalse($this->policy->delete($other, $post));
    }

    public function test_admin_cannot_modify_posts_of_others(): void
    {
        $owner = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($this->policy->view($admin, $post));
        $this->assertFalse($this->policy->update($admin, $post));
        $this->assertFalse($this->policy->delete($admin, $post));
    }
}
