<?php

namespace App\Http\Middleware;

use App\Features\Post\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePostOwnership
{
    /**
     * Ensure authors never access (or modify) posts owned by somebody else.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentUser = $request->user();

        if (!$currentUser) {
            abort(401, 'Unauthenticated');
        }

        $routePost = $request->route('post');
        if ($routePost) {
            $post = $routePost instanceof Post ? $routePost : Post::find($routePost);

            if ($post && $currentUser->id !== $post->user_id) {
                abort(403, 'Akses ditolak: Anda hanya dapat mengelola artikel milik Anda sendiri.');
            }
        }

        return $next($request);
    }
}
