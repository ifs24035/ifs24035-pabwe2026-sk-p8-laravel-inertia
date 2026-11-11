<?php

namespace Tests\Feature;

use App\Features\Post\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_accounts_and_sample_posts_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();
        $member = User::where('username', 'member')->firstOrFail();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('admin', $admin->password));
        $this->assertTrue($member->isMember());
        $this->assertTrue(Hash::check('member', $member->password));

        $this->assertEquals(7, Post::count());
        $this->assertEquals(6, Post::where('is_published', true)->count());
        $this->assertEquals(6, Post::distinct()->pluck('category')->count());
    }
}
