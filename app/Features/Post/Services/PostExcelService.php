<?php

namespace App\Features\Post\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostExcelService
{
    /**
     * Column layout shared by export and import, so an exported file can be imported again.
     */
    public const HEADERS = ['No', 'Judul', 'Kategori', 'Ringkasan', 'Konten', 'Status', 'Penulis', 'Tanggal Publikasi', 'Dibuat'];

    public function __construct(
        protected PostRepositoryInterface $postRepository,
        protected PostService $postService,
    ) {}

    /**
     * @param Collection<int, Post> $posts
     */
    public function buildSpreadsheet(Collection $posts, string $sheetTitle = 'Artikel'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $headerRange = 'A1:I1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowNumber = 2;
        foreach ($posts->values() as $index => $post) {
            $sheet->fromArray([
                $index + 1,
                $post->title,
                $post->category,
                $post->excerpt ?? '-',
                $post->content,
                $post->is_published ? 'Published' : 'Draft',
                $post->user?->name ?? '-',
                $post->published_at ? $post->published_at->format('Y-m-d H:i') : '-',
                $post->created_at ? $post->created_at->format('Y-m-d H:i') : '-',
            ], null, "A{$rowNumber}");
            $rowNumber++;
        }

        $lastRow = max(2, $rowNumber - 1);
        $sheet->getStyle("A1:I{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (['A', 'B', 'C', 'D', 'F', 'G', 'H', 'I'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getColumnDimension('E')->setWidth(60);

        return $spreadsheet;
    }

    /**
     * @param Collection<int, Post> $posts
     */
    public function exportToResponse(Collection $posts, string $filename = 'artikel.xlsx'): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($posts);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import posts from a spreadsheet. Columns: B=Judul, C=Kategori, D=Ringkasan, E=Konten, F=Status.
     * Rows without a title are skipped; every row is saved with a single insert query.
     *
     * @return array{imported: int, skipped: int}
     */
    public function import(string $filePath, int $userId): array
    {
        $rows = IOFactory::load($filePath)->getActiveSheet()->toArray(null, true, true, true);
        array_shift($rows); // header row

        $records = [];
        $reservedSlugs = [];
        $skipped = 0;
        $now = Carbon::now();

        foreach ($rows as $row) {
            $title = trim((string) ($row['B'] ?? ''));
            if ($title === '' || in_array(mb_strtolower($title), ['judul', 'title'], true)) {
                $skipped++;
                continue;
            }

            $content = trim((string) ($row['E'] ?? ''));
            $excerptRaw = trim((string) ($row['D'] ?? ''));
            $isPublished = in_array(mb_strtolower(trim((string) ($row['F'] ?? ''))), ['published', 'publikasi', 'terbit', '1', 'true'], true);

            $slug = $this->postService->generateUniqueSlug($title, null, $reservedSlugs);
            $reservedSlugs[] = $slug;

            $records[] = [
                'user_id' => $userId,
                'title' => mb_substr($title, 0, 255),
                'slug' => $slug,
                'category' => PostCategory::fromLoose($row['C'] ?? null)->value,
                'excerpt' => $this->postService->resolveExcerpt($excerptRaw === '-' ? '' : $excerptRaw, $content),
                'content' => $content === '' ? '-' : $content,
                'cover_image_path' => null,
                'is_published' => $isPublished,
                'published_at' => $isPublished ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->postRepository->insertMany($records);

        return [
            'imported' => count($records),
            'skipped' => $skipped,
        ];
    }
}
