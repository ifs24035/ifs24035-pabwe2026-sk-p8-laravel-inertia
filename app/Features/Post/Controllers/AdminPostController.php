<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPostController
{
    /**
     * Read-only monitoring of every post in the system.
     */
    public function index(Request $request, PostService $service): Response
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $status = $request->query('status');

        $posts = $service->getAllPosts($search ?: null, $category ?: null, $status ?: null, 10);

        return Inertia::render('Admin/PostList', [
            'posts' => $posts,
            'filters' => [
                'search' => $search ?? '',
                'category' => $category ?? '',
                'status' => $status ?? '',
            ],
            'categories' => PostCategory::options(),
        ]);
    }

    /**
     * Export every post in the system to Excel.
     */
    public function exportExcel(PostService $service, PostExcelService $excelService): StreamedResponse
    {
        return $excelService->exportToResponse(
            $service->getEveryPost(),
            'semua-artikel-'.now()->format('Ymd-His').'.xlsx'
        );
    }
}
