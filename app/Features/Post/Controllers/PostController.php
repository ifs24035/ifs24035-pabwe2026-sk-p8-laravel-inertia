<?php

namespace App\Features\Post\Controllers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController
{
    public function index(Request $request, PostService $service): Response
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $status = $request->query('status');

        $posts = $service->getPostsForUser(
            (int) Auth::id(),
            $search ?: null,
            $category ?: null,
            $status ?: null,
            8
        );

        return Inertia::render('Post/Index', [
            'posts' => $posts,
            'filters' => [
                'search' => $search ?? '',
                'category' => $category ?? '',
                'status' => $status ?? '',
            ],
            'categories' => PostCategory::options(),
            'stats' => $service->getStatsForUser((int) Auth::id()),
        ]);
    }

    public function store(Request $request, PostService $service): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $service->createPost((int) Auth::id(), $validated);

        return back()->with('success', 'Artikel baru berhasil ditambahkan.');
    }

    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        return Inertia::render('Post/Show', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category' => $post->category,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'cover_image_path' => $post->cover_image_path,
                'is_published' => $post->is_published,
                'published_at' => $post->published_at ? $post->published_at->format('d M Y H:i') : null,
                'created_at' => $post->created_at ? $post->created_at->format('d M Y H:i') : null,
                'updated_at' => $post->updated_at ? $post->updated_at->format('d M Y H:i') : null,
            ],
            'categories' => PostCategory::options(),
        ]);
    }

    public function update(Request $request, Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $validated = $request->validate($this->rules());

        $service->updatePost($post, $validated);

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $service->deletePost($post);

        return redirect()->route('dashboard.posts.index')->with('success', 'Artikel berhasil dihapus.');
    }

    public function publish(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->togglePublish($post);

        return back()->with('success', 'Status publikasi artikel berhasil diperbarui.');
    }

    public function updateCover(Request $request, Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $request->validate([
            'coverPhoto' => ['required', 'image', 'max:5120'],
        ]);

        $service->updateCoverImage($post, $request->file('coverPhoto'));

        return back()->with('success', 'Foto cover artikel berhasil diperbarui.');
    }

    public function deleteCover(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->deleteCoverImage($post);

        return back()->with('success', 'Foto cover artikel berhasil dihapus.');
    }

    public function exportExcel(PostService $service, PostExcelService $excelService): StreamedResponse
    {
        $posts = $service->getAllPostsForUser((int) Auth::id());

        return $excelService->exportToResponse($posts, 'artikel-saya-'.now()->format('Ymd-His').'.xlsx');
    }

    public function importExcel(Request $request, PostExcelService $excelService): RedirectResponse
    {
        $request->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = $excelService->import($request->file('importFile')->getRealPath(), (int) Auth::id());

        return back()->with('success', "Berhasil mengimpor {$result['imported']} artikel ({$result['skipped']} baris dilewati).");
    }

    public function generateAi(Request $request, AiClientInterface $aiClient): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:1000'],
            'count' => ['nullable', 'integer', 'min:1', 'max:10'],
        ], [
            'prompt.required' => 'Topik / instruksi artikel harus diisi minimal 3 karakter.',
            'prompt.min' => 'Topik / instruksi artikel harus minimal 3 karakter.',
            'count.max' => 'Jumlah artikel maksimal 10.',
            'count.min' => 'Jumlah artikel minimal 1.',
        ]);

        try {
            $items = $aiClient->generatePosts($validated['prompt'], (int) ($validated['count'] ?? 1));

            return response()->json([
                'success' => true,
                'items' => array_map(fn ($item) => $item->toArray(), $items),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghasilkan artikel dengan AI: '.$e->getMessage(),
            ], 422);
        }
    }

    public function saveAi(Request $request, PostService $service): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', Rule::enum(PostCategory::class)],
            'items.*.excerpt' => ['nullable', 'string', 'max:500'],
            'items.*.content' => ['required', 'string'],
        ]);

        foreach ($validated['items'] as $item) {
            $service->createPost((int) Auth::id(), [
                'title' => $item['title'],
                'category' => $item['category'] ?? PostCategory::UMUM->value,
                'excerpt' => $item['excerpt'] ?? null,
                'content' => $item['content'],
                'is_published' => false,
            ]);
        }

        $count = count($validated['items']);

        return back()->with('success', "Berhasil menyimpan {$count} artikel hasil AI sebagai draf.");
    }

    /**
     * Validation rules shared by create and update.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(PostCategory::class)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
