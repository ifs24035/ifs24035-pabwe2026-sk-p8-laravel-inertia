<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminUserService(new PostRepository());
    }

    public function test_get_users_list_with_posts_count_and_search(): void
    {
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'username' => 'budi', 'email' => 'budi@test.com']);
        User::factory()->create(['name' => 'Citra Lestari', 'username' => 'citra', 'email' => 'citra@test.com']);
        Post::factory()->count(2)->create(['user_id' => $budi->id]);

        $all = $this->service->getUsersList(null, 10);
        $this->assertEquals(2, $all->total());

        // Case-insensitive search by name, username and email
        $byName = $this->service->getUsersList('BUDI SAN', 10);
        $this->assertEquals(1, $byName->total());
        $this->assertEquals(2, $byName->items()[0]->posts_count);

        $this->assertEquals(1, $this->service->getUsersList('citra', 10)->total());
        $this->assertEquals(1, $this->service->getUsersList('citra@test', 10)->total());
        $this->assertEquals(0, $this->service->getUsersList('tidak-ada', 10)->total());
    }

    public function test_get_user_posts_is_scoped_to_the_user(): void
    {
        $budi = User::factory()->create();
        $other = User::factory()->create();

        Post::factory()->published()->create(['user_id' => $budi->id, 'title' => 'Artikel Budi']);
        Post::factory()->draft()->create(['user_id' => $budi->id, 'title' => 'Draf Budi']);
        Post::factory()->create(['user_id' => $other->id, 'title' => 'Artikel Lain']);

        $this->assertEquals(2, $this->service->getUserPosts($budi->id)->total());
        $this->assertEquals(1, $this->service->getUserPosts($budi->id, null, null, 'published')->total());
        $this->assertEquals(1, $this->service->getUserPosts($budi->id, 'Draf')->total());
    }
}
