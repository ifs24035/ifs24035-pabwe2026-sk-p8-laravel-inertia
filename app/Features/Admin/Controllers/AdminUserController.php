<?php

namespace App\Features\Admin\Controllers;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController
{
    public function index(Request $request, AdminUserService $adminService): Response
    {
        $search = $request->query('search');
        $users = $adminService->getUsersList($search ?: null, 10);

        return Inertia::render('Admin/UserList', [
            'users' => $users,
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    public function posts(Request $request, User $user, AdminUserService $adminService): Response
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $status = $request->query('status');

        $posts = $adminService->getUserPosts(
            $user->id,
            $search ?: null,
            $category ?: null,
            $status ?: null,
            10
        );

        return Inertia::render('Admin/UserPostList', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'posts' => $posts,
            'filters' => [
                'search' => $search ?? '',
                'category' => $category ?? '',
                'status' => $status ?? '',
            ],
            'categories' => PostCategory::options(),
        ]);
    }

    public function exportUserPosts(User $user, PostService $service, PostExcelService $excelService): StreamedResponse
    {
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $user->name);

        return $excelService->exportToResponse(
            $service->getAllPostsForUser($user->id),
            "artikel-{$safeName}-".now()->format('Ymd-His').'.xlsx'
        );
    }
}
