<?php

namespace Tests\Feature\Post;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard/posts')->assertRedirect(route('login'));
        $this->post('/dashboard/posts', [])->assertRedirect(route('login'));
    }

    public function test_member_sees_only_own_posts_with_stats(): void
    {
        $user = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Artikel Saya']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Draf Saya']);
        Post::factory()->published()->create(['user_id' => $other->id, 'title' => 'Artikel Orang Lain']);

        $this->actingAs($user)
            ->get('/dashboard/posts')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Index')
                ->has('posts.data', 2)
                ->where('stats.total', 2)
                ->where('stats.published', 1)
                ->where('stats.draft', 1)
                ->has('categories', 6)
            );
    }

    public function test_search_category_and_status_filters(): void
    {
        $user = User::factory()->member()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Belajar Laravel', 'category' => 'Teknologi', 'content' => 'konten a']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Catatan Harian', 'category' => 'Opini', 'content' => 'konten b']);

        $this->actingAs($user)->get('/dashboard/posts?search=LARAVEL')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Belajar Laravel')
                ->where('filters.search', 'LARAVEL')
            );

        $this->actingAs($user)->get('/dashboard/posts?category=Opini')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Catatan Harian')
            );

        $this->actingAs($user)->get('/dashboard/posts?status=published')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Belajar Laravel')
            );

        $this->actingAs($user)->get('/dashboard/posts?status=draft')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Catatan Harian')
            );
    }

    public function test_create_post_validation(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts', [
                'title' => '',
                'category' => 'Kategori Palsu',
                'content' => '',
                'excerpt' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors(['title', 'category', 'content', 'excerpt']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_create_post_success(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts', [
                'title' => 'Artikel Baru',
                'category' => 'Pendidikan',
                'excerpt' => 'Ringkasan artikel',
                'content' => '## Isi',
                'is_published' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'title' => 'Artikel Baru',
            'slug' => 'artikel-baru',
            'category' => 'Pendidikan',
            'is_published' => true,
        ]);
        $this->assertNotNull(Post::first()->published_at);

        // Same title again -> unique slug, saved as draft when is_published is omitted
        $this->actingAs($user)
            ->post('/dashboard/posts', ['title' => 'Artikel Baru', 'category' => 'Umum', 'content' => 'Isi lain'])
            ->assertSessionHasNoErrors();

        $second = Post::where('slug', 'artikel-baru-2')->first();
        $this->assertNotNull($second);
        $this->assertFalse($second->is_published);
        $this->assertNull($second->published_at);
    }

    public function test_show_own_post(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Detail Artikel', 'content' => '## Isi']);

        $this->actingAs($user)
            ->get("/dashboard/posts/{$post->id}")
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Show')
                ->where('post.title', 'Detail Artikel')
                ->where('post.content', '## Isi')
                ->where('post.is_published', true)
                ->has('post.published_at')
                ->has('categories', 6)
            );

        $draft = Post::factory()->draft()->create(['user_id' => $user->id]);
        $this->actingAs($user)
            ->get("/dashboard/posts/{$draft->id}")
            ->assertInertia(fn (Assert $page) => $page->where('post.published_at', null));
    }

    public function test_update_post(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Judul Lama', 'slug' => 'judul-lama']);

        $this->actingAs($user)
            ->put("/dashboard/posts/{$post->id}", [
                'title' => 'Judul Baru',
                'category' => 'Bisnis',
                'excerpt' => 'Ringkasan baru',
                'content' => 'Konten baru',
                'is_published' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $post->refresh();
        $this->assertEquals('Judul Baru', $post->title);
        $this->assertEquals('judul-lama', $post->slug);
        $this->assertEquals('Bisnis', $post->category);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);

        $this->actingAs($user)
            ->put("/dashboard/posts/{$post->id}", ['title' => '', 'category' => 'Bisnis', 'content' => 'x'])
            ->assertSessionHasErrors(['title']);
    }

    public function test_toggle_publish(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->draft()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patch("/dashboard/posts/{$post->id}/publish")
            ->assertSessionHas('success');
        $this->assertTrue($post->fresh()->is_published);
        $this->assertNotNull($post->fresh()->published_at);

        $this->actingAs($user)->patch("/dashboard/posts/{$post->id}/publish");
        $this->assertFalse($post->fresh()->is_published);
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_update_and_delete_cover_image(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/dashboard/posts/{$post->id}/cover", ['coverPhoto' => UploadedFile::fake()->image('cover.jpg', 600, 400)])
            ->assertSessionHas('success');

        $post->refresh();
        $this->assertNotNull($post->cover_image_path);
        Storage::disk('public')->assertExists($post->cover_image_path);
        $path = $post->cover_image_path;

        // Non-image is rejected
        $this->actingAs($user)
            ->post("/dashboard/posts/{$post->id}/cover", ['coverPhoto' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('coverPhoto');

        $this->actingAs($user)
            ->delete("/dashboard/posts/{$post->id}/cover")
            ->assertSessionHas('success');

        $this->assertNull($post->fresh()->cover_image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_post(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete("/dashboard/posts/{$post->id}")
            ->assertRedirect(route('dashboard.posts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_export_excel_contains_only_own_posts(): void
    {
        $user = User::factory()->member()->create();
        Post::factory()->count(3)->create(['user_id' => $user->id]);
        Post::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard/posts/export');

        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('artikel-saya-', (string) $response->headers->get('content-disposition'));
    }

    public function test_import_excel(): void
    {
        $user = User::factory()->member()->create();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['No', 'Title', 'Category', 'Excerpt', 'Content', 'Status'],
            [1, 'Artikel Impor 1', 'Teknologi', 'Ringkasan', 'Konten satu', 'Published'],
            [2, 'Artikel Impor 2', 'Opini', null, 'Konten dua', 'Draft'],
            [3, '', 'Opini', null, 'Tanpa judul', 'Draft'],
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);
        $uploaded = new UploadedFile($tempFile, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($user)
            ->post('/dashboard/posts/import', ['importFile' => $uploaded])
            ->assertSessionHas('success', 'Berhasil mengimpor 2 artikel (1 baris dilewati).');

        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'Artikel Impor 1', 'is_published' => true]);
        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'Artikel Impor 2', 'is_published' => false]);

        @unlink($tempFile);
    }

    public function test_import_requires_a_spreadsheet_file(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts/import', [])
            ->assertSessionHasErrors('importFile');

        $this->actingAs($user)
            ->post('/dashboard/posts/import', ['importFile' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('importFile');
    }

    public function test_generate_ai_success_and_failure(): void
    {
        $user = User::factory()->member()->create();

        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generatePosts')->willReturn([
            new GeneratedPostItem('Artikel AI', 'Teknologi', 'Ringkasan AI', '## Isi AI'),
        ]);
        $this->app->instance(AiClientInterface::class, $stub);

        $this->actingAs($user)
            ->postJson('/dashboard/posts/ai-generate', ['prompt' => 'Tulis tentang Laravel', 'count' => 1])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'items' => [['title' => 'Artikel AI', 'category' => 'Teknologi']],
            ]);

        // count is optional (defaults to 1)
        $this->actingAs($user)
            ->postJson('/dashboard/posts/ai-generate', ['prompt' => 'Tulis tentang React'])
            ->assertStatus(200);

        // AI client failure -> friendly 422 JSON
        $failing = $this->createStub(AiClientInterface::class);
        $failing->method('generatePosts')->willThrowException(new \RuntimeException('Koneksi gagal'));
        $this->app->instance(AiClientInterface::class, $failing);

        $this->actingAs($user)
            ->postJson('/dashboard/posts/ai-generate', ['prompt' => 'Tulis tentang Laravel', 'count' => 2])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Gagal menghasilkan artikel dengan AI: Koneksi gagal',
            ]);
    }

    public function test_generate_ai_validates_prompt_and_count_limits(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/dashboard/posts/ai-generate', ['prompt' => 'ab', 'count' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors('prompt');

        $this->actingAs($user)
            ->postJson('/dashboard/posts/ai-generate', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('prompt');
    }

    public function test_generate_ai_rejects_out_of_range_count(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/dashboard/posts/ai-generate', ['prompt' => 'Topik valid', 'count' => 11])
            ->assertStatus(422)
            ->assertJsonValidationErrors('count');

        $this->actingAs($user)
            ->postJson('/dashboard/posts/ai-generate', ['prompt' => 'Topik valid', 'count' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('count');
    }

    public function test_save_ai_creates_draft_posts(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts/ai-save', [
                'items' => [
                    ['title' => 'AI Item 1', 'category' => 'Teknologi', 'excerpt' => 'Ringkasan 1', 'content' => 'Isi 1'],
                    ['title' => 'AI Item 2', 'category' => null, 'excerpt' => null, 'content' => 'Isi 2'],
                ],
            ])
            ->assertSessionHas('success', 'Berhasil menyimpan 2 artikel hasil AI sebagai draf.');

        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'AI Item 1', 'category' => 'Teknologi', 'is_published' => false]);
        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'AI Item 2', 'category' => 'Umum', 'is_published' => false]);
    }

    public function test_save_ai_validation(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)->post('/dashboard/posts/ai-save', [])->assertSessionHasErrors('items');

        $this->actingAs($user)
            ->post('/dashboard/posts/ai-save', ['items' => [['title' => '', 'category' => 'Salah', 'content' => '']]])
            ->assertSessionHasErrors(['items.0.title', 'items.0.category', 'items.0.content']);
    }

    public function test_cannot_access_other_users_post(): void
    {
        $user = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $post = Post::factory()->published()->create(['user_id' => $other->id, 'title' => 'Asli']);

        $this->actingAs($user);

        $this->get("/dashboard/posts/{$post->id}")->assertStatus(403);
        $this->put("/dashboard/posts/{$post->id}", ['title' => 'Hacked', 'category' => 'Umum', 'content' => 'x'])->assertStatus(403);
        $this->patch("/dashboard/posts/{$post->id}/publish")->assertStatus(403);
        $this->delete("/dashboard/posts/{$post->id}")->assertStatus(403);
        $this->post("/dashboard/posts/{$post->id}/cover", ['coverPhoto' => UploadedFile::fake()->image('c.jpg')])->assertStatus(403);
        $this->delete("/dashboard/posts/{$post->id}/cover")->assertStatus(403);

        $this->assertEquals('Asli', $post->fresh()->title);
        $this->assertTrue($post->fresh()->is_published);
    }

    public function test_admin_cannot_modify_posts_of_other_users(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $member->id, 'title' => 'Milik Member']);

        $this->actingAs($admin);

        $this->get("/dashboard/posts/{$post->id}")->assertStatus(403);
        $this->put("/dashboard/posts/{$post->id}", ['title' => 'Diubah', 'category' => 'Umum', 'content' => 'x'])->assertStatus(403);
        $this->delete("/dashboard/posts/{$post->id}")->assertStatus(403);

        $this->assertEquals('Milik Member', $post->fresh()->title);
    }

    public function test_unknown_post_returns_404(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)->get('/dashboard/posts/999999')->assertStatus(404);
    }
}
