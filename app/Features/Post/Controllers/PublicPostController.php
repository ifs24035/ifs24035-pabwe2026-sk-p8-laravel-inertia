<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPostController
{
    /**
     * Public gallery of published articles (home page and /articles).
     */
    public function index(Request $request, PostService $service): Response
    {
        $search = $request->query('search');
        $category = $request->query('category');

        $posts = $service->getPublicPosts($search ?: null, $category ?: null, 9);

        return Inertia::render('Public/PostIndex', [
            'posts' => $posts,
            'filters' => [
                'search' => $search ?? '',
                'category' => $category ?? '',
            ],
            'categories' => PostCategory::options(),
        ]);
    }

    /**
     * Read a published article by its slug.
     */
    public function show(string $slug, PostService $service): Response
    {
        $post = $service->getPublicPost($slug);

        return Inertia::render('Public/PostShow', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category' => $post->category,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'cover_image_path' => $post->cover_image_path,
                'published_at' => $post->published_at ? $post->published_at->format('d M Y') : null,
                'author' => [
                    'name' => $post->user->name,
                    'username' => $post->user->username,
                    'profile_photo_path' => $post->user->profile_photo_path,
                ],
            ],
            'related' => $service->getRelatedPosts($post, 3),
            'categories' => PostCategory::options(),
        ]);
    }
}
