<?php

namespace Tests\Unit\Services;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class PostExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PostExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PostExcelService(new PostService(new PostRepository()));
    }

    public function test_build_spreadsheet_and_export_response(): void
    {
        $user = User::factory()->create(['name' => 'Penulis Satu']);
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Artikel Terbit', 'excerpt' => 'Ringkasan']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => '=1+1', 'excerpt' => null]);
        $posts = Post::with('user')->orderBy('id')->get();

        $spreadsheet = $this->service->buildSpreadsheet($posts, 'Custom Posts');
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertInstanceOf(Spreadsheet::class, $spreadsheet);
        $this->assertEquals('Custom Posts', $sheet->getTitle());
        $this->assertEquals('Title', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Artikel Terbit', $sheet->getCell('B2')->getValue());
        $this->assertEquals('Published', $sheet->getCell('F2')->getValue());
        $this->assertEquals('Draft', $sheet->getCell('F3')->getValue());
        $this->assertEquals('-', $sheet->getCell('D3')->getValue());
        $this->assertEquals('-', $sheet->getCell('G3')->getValue());
        $this->assertEquals('Penulis Satu', $sheet->getCell('I2')->getValue());

        // Formula injection is neutralised: "=1+1" is stored as plain text
        $this->assertEquals(DataType::TYPE_STRING, $sheet->getCell('B3')->getDataType());

        $response = $this->service->exportToResponse($posts, 'test.xlsx');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));

        ob_start();
        $response->sendContent();
        $outputContent = ob_get_clean();
        $this->assertNotEmpty($outputContent);
    }

    public function test_export_empty_collection_and_missing_dates(): void
    {
        $empty = $this->service->buildSpreadsheet(new \Illuminate\Database\Eloquent\Collection());
        $this->assertEquals('Posts', $empty->getActiveSheet()->getTitle());
        $this->assertNull($empty->getActiveSheet()->getCell('B2')->getValue());

        // A post without relations / timestamps still exports ("-" placeholders)
        $post = new Post(['title' => 'Tanpa Relasi', 'category' => 'Umum', 'content' => 'Isi']);
        $sheet = $this->service->buildSpreadsheet(new \Illuminate\Database\Eloquent\Collection([$post]))->getActiveSheet();
        $this->assertEquals('-', $sheet->getCell('H2')->getValue());
        $this->assertEquals('-', $sheet->getCell('I2')->getValue());
    }

    public function test_import_spreadsheet(): void
    {
        $user = User::factory()->create();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['No', 'Title', 'Category', 'Excerpt', 'Content', 'Status'],
            [1, 'Artikel Impor Terbit', 'teknologi', 'Ringkasan impor', 'Konten impor satu', 'Published'],
            [2, 'Artikel Impor Draf', 'Kategori Aneh', '-', 'Konten impor dua', 'draft'],
            [3, 'Artikel Tanpa Konten', 'Umum', 'x', '', 'Published'],
            [4, '', 'Umum', 'x', 'Judul kosong', 'Published'],
            [5, 'Artikel Bahasa Indonesia', 'Gaya Hidup', '', 'Konten impor tiga', 'Terbit'],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);

        $result = $this->service->import($tempPath, $user->id);
        @unlink($tempPath);

        $this->assertSame(['imported' => 3, 'skipped' => 2], $result);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'title' => 'Artikel Impor Terbit',
            'slug' => 'artikel-impor-terbit',
            'category' => 'Teknologi',
            'excerpt' => 'Ringkasan impor',
            'is_published' => true,
        ]);
        $this->assertDatabaseHas('posts', [
            'title' => 'Artikel Impor Draf',
            'category' => 'Umum',
            'is_published' => false,
        ]);
        $this->assertDatabaseHas('posts', [
            'title' => 'Artikel Bahasa Indonesia',
            'category' => 'Gaya Hidup',
            'is_published' => true,
        ]);
        $this->assertDatabaseMissing('posts', ['title' => 'Artikel Tanpa Konten']);

        // "-" excerpt becomes an auto-generated excerpt, never the literal dash
        $this->assertEquals('Konten impor dua', Post::where('title', 'Artikel Impor Draf')->value('excerpt'));
    }

    public function test_exported_file_can_be_imported_again(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Round Trip', 'slug' => 'round-trip', 'category' => 'Bisnis', 'content' => 'Isi round trip']);

        $spreadsheet = $this->service->buildSpreadsheet(Post::with('user')->get());
        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);

        $result = $this->service->import($tempPath, $user->id);
        @unlink($tempPath);

        $this->assertSame(['imported' => 1, 'skipped' => 0], $result);
        $this->assertEquals(2, Post::where('title', 'Round Trip')->count());
        $this->assertDatabaseHas('posts', ['slug' => 'round-trip-2', 'category' => 'Bisnis', 'is_published' => true]);
    }
}
