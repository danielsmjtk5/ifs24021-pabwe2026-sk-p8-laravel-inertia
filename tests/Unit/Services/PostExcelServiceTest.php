<?php

namespace Tests\Unit\Services;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class PostExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    private PostExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $repo = new PostRepository();
        $this->service = new PostExcelService($repo, new PostService($repo));
    }

    public function test_build_spreadsheet_and_stream_response(): void
    {
        $user = User::factory()->create(['name' => 'Penulis A']);
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Satu', 'excerpt' => null]);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Dua']);
        $posts = Post::with('user')->orderBy('id')->get();

        $sheet = $this->service->buildSpreadsheet($posts)->getActiveSheet();

        $this->assertSame('Judul', $sheet->getCell('B1')->getValue());
        $this->assertSame('Satu', $sheet->getCell('B2')->getValue());
        $this->assertSame('-', $sheet->getCell('D2')->getValue());
        $this->assertSame('Published', $sheet->getCell('F2')->getValue());
        $this->assertSame('Draft', $sheet->getCell('F3')->getValue());
        $this->assertSame('-', $sheet->getCell('H3')->getValue());
        $this->assertSame('Penulis A', $sheet->getCell('G2')->getValue());

        $response = $this->service->exportToResponse($posts, 'tes.xlsx');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        ob_start();
        $response->sendContent();
        $this->assertStringStartsWith('PK', (string) ob_get_clean());
    }

    public function test_build_spreadsheet_handles_missing_author_and_dates(): void
    {
        $post = Post::factory()->make(['title' => 'Tanpa Penulis']);
        $post->setRelation('user', null);

        $sheet = $this->service->buildSpreadsheet(new \Illuminate\Database\Eloquent\Collection([$post]))->getActiveSheet();

        $this->assertSame('-', $sheet->getCell('G2')->getValue());
        $this->assertSame('-', $sheet->getCell('I2')->getValue());
    }

    public function test_import_round_trip_skips_empty_rows_and_makes_unique_slugs(): void
    {
        $user = User::factory()->create();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            PostExcelService::HEADERS,
            [1, 'Artikel Sama', 'teknologi', 'Ringkas', '## Isi', 'Published'],
            [2, 'Artikel Sama', 'Kategori Asing', '-', 'Isi kedua panjang', 'Draft'],
            [3, '', 'Umum', '', '', ''],
            [4, 'Judul', 'Umum', '', '', ''],
            [5, 'Tanpa Konten', 'Opini', '', '', ''],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $result = $this->service->import($path, $user->id);
        @unlink($path);

        $this->assertSame(['imported' => 3, 'skipped' => 2], $result);
        $slugs = Post::where('user_id', $user->id)->orderBy('id')->pluck('slug')->all();
        $this->assertSame(['artikel-sama', 'artikel-sama-2', 'tanpa-konten'], $slugs);

        $first = Post::where('slug', 'artikel-sama')->first();
        $this->assertSame('Teknologi', $first->category);
        $this->assertTrue($first->is_published);
        $this->assertNotNull($first->published_at);

        $second = Post::where('slug', 'artikel-sama-2')->first();
        $this->assertSame('Umum', $second->category);
        $this->assertSame('Isi kedua panjang', $second->excerpt);
        $this->assertFalse($second->is_published);

        $this->assertSame('-', Post::where('slug', 'tanpa-konten')->value('content'));
    }
}
