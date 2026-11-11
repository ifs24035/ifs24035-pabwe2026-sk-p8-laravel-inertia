<?php

namespace Tests\Unit\Middleware;

use App\Features\Post\Models\Post;
use App\Http\Middleware\EnsurePostOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsurePostOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a request that carries the given user and {post} route parameter.
     */
    protected function makeRequest(?User $user, mixed $postParameter = null): Request
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $route = new Route('GET', '/test/{post}', []);
        $route->bind($request);
        if ($postParameter !== null) {
            $route->setParameter('post', $postParameter);
        }
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    public function test_blocks_unauthenticated_request(): void
    {
        $middleware = new EnsurePostOwnership();
        $request = Request::create('/dashboard/posts', 'GET');

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_allows_request_without_post_parameter(): void
    {
        $middleware = new EnsurePostOwnership();
        $member = User::factory()->member()->create();

        $response = $middleware->handle($this->makeRequest($member), fn () => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_owner_can_pass_with_model_instance(): void
    {
        $middleware = new EnsurePostOwnership();
        $member = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $member->id]);

        $response = $middleware->handle($this->makeRequest($member, $post), fn () => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_owner_can_pass_with_raw_id_parameter(): void
    {
        $middleware = new EnsurePostOwnership();
        $member = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $member->id]);

        $response = $middleware->handle($this->makeRequest($member, (string) $post->id), fn () => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_member_cannot_access_post_of_another_member(): void
    {
        $middleware = new EnsurePostOwnership();
        $member = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $other->id]);

        $this->expectException(HttpException::class);
        $middleware->handle($this->makeRequest($member, $post), fn () => new Response('OK'));
    }

    public function test_admin_cannot_access_dashboard_post_of_another_user(): void
    {
        $middleware = new EnsurePostOwnership();
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $member->id]);

        $this->expectException(HttpException::class);
        $middleware->handle($this->makeRequest($admin, (string) $post->id), fn () => new Response('OK'));
    }

    public function test_unknown_post_id_is_left_to_the_framework(): void
    {
        $middleware = new EnsurePostOwnership();
        $member = User::factory()->member()->create();

        $response = $middleware->handle($this->makeRequest($member, '999999'), fn () => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
