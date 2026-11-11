<?php

namespace App\Features\Post\Services;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostExcelService
{
    /**
     * Status cell values that are treated as "published" on import.
     *
     * @var array<int, string>
     */
    protected const PUBLISHED_ALIASES = ['published', 'publish', 'terbit', 'dipublikasikan'];

    public function __construct(
        protected PostService $postService
    ) {}

    /**
     * Build a Spreadsheet instance containing posts data.
     *
     * @param Collection<int, Post> $posts
     */
    public function buildSpreadsheet(Collection $posts, string $sheetTitle = 'Posts'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        $headers = ['No', 'Title', 'Category', 'Excerpt', 'Content', 'Status', 'Published At', 'Created At', 'Author'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue("{$columns[$index]}1", $header);
        }

        // Header Styling
        $headerRange = 'A1:I1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4338CA');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Text cells are written as explicit strings so a title such as "=SUM(1+1)"
        // can never be evaluated as a formula by spreadsheet software (formula injection).
        $text = fn (string $cell, mixed $value) => $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);

        $rowNumber = 2;
        foreach ($posts as $idx => $post) {
            $sheet->setCellValue("A{$rowNumber}", $idx + 1);
            $text("B{$rowNumber}", $post->title);
            $text("C{$rowNumber}", $post->category);
            $text("D{$rowNumber}", $post->excerpt ?? '-');
            $text("E{$rowNumber}", $post->content);
            $text("F{$rowNumber}", $post->is_published ? 'Published' : 'Draft');
            $text("G{$rowNumber}", $post->published_at ? $post->published_at->format('Y-m-d H:i') : '-');
            $text("H{$rowNumber}", $post->created_at ? $post->created_at->format('Y-m-d H:i') : '-');
            $text("I{$rowNumber}", $post->user?->name ?? '-');
            $rowNumber++;
        }

        $lastRow = max(2, $rowNumber - 1);
        $sheet->getStyle("A1:I{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach ($columns as $column) {
            // Long text columns get a fixed width; the rest is auto sized.
            if ($column === 'D' || $column === 'E') {
                $sheet->getColumnDimension($column)->setWidth(50);
                continue;
            }
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Export posts as a downloadable streamed response.
     *
     * @param Collection<int, Post> $posts
     */
    public function exportToResponse(Collection $posts, string $filename = 'posts.xlsx'): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($posts);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import posts from an uploaded spreadsheet file path.
     * Expected columns: A No, B Title, C Category, D Excerpt, E Content, F Status.
     *
     * @return array{imported: int, skipped: int}
     */
    public function import(string $filePath, int $userId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $imported = 0;
        $skipped = 0;

        // Skip header row
        foreach (array_slice($rows, 1) as $row) {
            $title = trim((string) ($row['B'] ?? ''));
            $content = trim((string) ($row['E'] ?? ''));

            if ($title === '' || $content === '') {
                $skipped++;
                continue;
            }

            $excerpt = trim((string) ($row['D'] ?? ''));
            $statusRaw = strtolower(trim((string) ($row['F'] ?? '')));

            $this->postService->createPost($userId, [
                'title' => substr($title, 0, 255),
                'category' => $this->resolveCategory(trim((string) ($row['C'] ?? ''))),
                'excerpt' => $excerpt === '-' ? null : $excerpt,
                'content' => $content,
                'is_published' => in_array($statusRaw, self::PUBLISHED_ALIASES, true),
            ]);

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    /**
     * Resolve a free-text category cell into a valid PostCategory value (fallback: Umum).
     */
    protected function resolveCategory(string $raw): string
    {
        foreach (PostCategory::cases() as $case) {
            if (mb_strtolower($case->value) === mb_strtolower($raw)) {
                return $case->value;
            }
        }

        return PostCategory::UMUM->value;
    }
}
