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
use RuntimeException;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->member()->create();
    }

    private function validData(array $overrides = []): array
    {
        return [
            'title' => 'Artikel Baru',
            'category' => 'Teknologi',
            'excerpt' => 'Ringkasan',
            'content' => '## Isi artikel',
            'is_published' => false,
            ...$overrides,
        ];
    }

    public function test_admin_cannot_open_writer_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard/posts')->assertForbidden();
    }

    public function test_index_shows_own_posts_with_stats_and_filters(): void
    {
        Post::factory()->published()->create(['user_id' => $this->member->id, 'title' => 'Sudah Terbit']);
        Post::factory()->draft()->create(['user_id' => $this->member->id, 'title' => 'Masih Draf']);
        Post::factory()->create(['title' => 'Milik orang lain']);

        $this->actingAs($this->member)->get('/dashboard/posts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Index')
                ->has('posts.data', 2)
                ->where('stats', ['total' => 2, 'published' => 1, 'draft' => 1])
                ->has('categories', 6)
            );

        $this->actingAs($this->member)->get('/dashboard/posts?status=draft&search=draf')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Masih Draf')
                ->where('filters.status', 'draft')
            );
    }

    public function test_store_validates_and_creates(): void
    {
        $this->actingAs($this->member)->post('/dashboard/posts', ['title' => '', 'category' => 'Salah', 'content' => ''])
            ->assertSessionHasErrors(['title', 'category', 'content']);

        $this->actingAs($this->member)->post('/dashboard/posts', $this->validData(['is_published' => true]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('posts', ['user_id' => $this->member->id, 'slug' => 'artikel-baru', 'is_published' => true]);
    }

    public function test_show_update_and_destroy(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->member->id]);

        $this->actingAs($this->member)->get("/dashboard/posts/{$post->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Show')
                ->where('post.id', $post->id)
                ->where('post.published_at', null)
            );

        $this->actingAs($this->member)->put("/dashboard/posts/{$post->id}", $this->validData(['title' => 'Diubah']))
            ->assertSessionHas('success');
        $this->assertSame('Diubah', $post->fresh()->title);

        $this->actingAs($this->member)->delete("/dashboard/posts/{$post->id}")
            ->assertRedirect(route('dashboard.posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_toggle_publish_both_directions(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->member->id]);

        $this->actingAs($this->member)->patch("/dashboard/posts/{$post->id}/publish")
            ->assertSessionHas('success', 'Artikel berhasil dipublikasikan.');
        $this->assertTrue($post->fresh()->is_published);

        $this->actingAs($this->member)->patch("/dashboard/posts/{$post->id}/publish")
            ->assertSessionHas('success', 'Artikel dikembalikan menjadi draf.');
        $this->assertFalse($post->fresh()->is_published);
    }

    public function test_cover_upload_validation_upload_and_delete(): void
    {
        Storage::fake('public');
        $post = Post::factory()->create(['user_id' => $this->member->id]);

        $this->actingAs($this->member)->post("/dashboard/posts/{$post->id}/cover", ['coverPhoto' => UploadedFile::fake()->create('x.pdf', 10)])
            ->assertSessionHasErrors('coverPhoto');

        $this->actingAs($this->member)->post("/dashboard/posts/{$post->id}/cover", ['coverPhoto' => UploadedFile::fake()->image('c.jpg')])
            ->assertSessionHas('success');
        $path = $post->fresh()->cover_image_path;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->member)->get("/dashboard/posts/{$post->id}")
            ->assertInertia(fn (Assert $page) => $page->where('post.cover_image_url', fn ($url) => str_contains($url, $path)));

        $this->actingAs($this->member)->delete("/dashboard/posts/{$post->id}/cover")->assertSessionHas('success');
        Storage::disk('public')->assertMissing($path);
    }

    public function test_member_cannot_touch_other_authors_post(): void
    {
        $other = Post::factory()->create();

        $this->actingAs($this->member)->get("/dashboard/posts/{$other->id}")->assertForbidden();
        $this->actingAs($this->member)->put("/dashboard/posts/{$other->id}", $this->validData())->assertForbidden();
        $this->actingAs($this->member)->delete("/dashboard/posts/{$other->id}")->assertForbidden();
        $this->actingAs($this->member)->patch("/dashboard/posts/{$other->id}/publish")->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $other->id]);
    }

    public function test_export_and_import_excel(): void
    {
        Post::factory()->create(['user_id' => $this->member->id]);

        $this->actingAs($this->member)->get('/dashboard/posts/export')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->member)->post('/dashboard/posts/import', [])->assertSessionHasErrors('importFile');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['No', 'Judul', 'Kategori', 'Ringkasan', 'Konten', 'Status'],
            [1, 'Dari Excel', 'Opini', '', 'Isi dari excel', 'Draft'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'xl').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile($path, 'artikel.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($this->member)->post('/dashboard/posts/import', ['importFile' => $file])
            ->assertSessionHas('success', 'Berhasil mengimpor 1 artikel.');
        $this->assertDatabaseHas('posts', ['user_id' => $this->member->id, 'title' => 'Dari Excel', 'category' => 'Opini']);
        @unlink($path);
    }

    public function test_ai_generate_success_validation_and_failure(): void
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generatePosts')->willReturn([new GeneratedPostItem('Judul AI', 'Teknologi', 'Ringkas', '## Isi')]);
        $this->app->instance(AiClientInterface::class, $stub);

        $this->actingAs($this->member)->postJson('/dashboard/posts/ai-generate', ['prompt' => 'Laravel', 'count' => 1])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('items.0.title', 'Judul AI');

        $this->actingAs($this->member)->postJson('/dashboard/posts/ai-generate', ['prompt' => 'ab', 'count' => 9])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['prompt', 'count']);

        $failing = $this->createStub(AiClientInterface::class);
        $failing->method('generatePosts')->willThrowException(new RuntimeException('Invalid API key provided.'));
        $this->app->instance(AiClientInterface::class, $failing);

        $this->actingAs($this->member)->postJson('/dashboard/posts/ai-generate', ['prompt' => 'Laravel', 'count' => 2])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Gagal membuat artikel dengan AI: Invalid API key provided.');
    }

    public function test_ai_save_stores_drafts(): void
    {
        $this->actingAs($this->member)->post('/dashboard/posts/ai-save', ['items' => []])->assertSessionHasErrors('items');

        $this->actingAs($this->member)->post('/dashboard/posts/ai-save', ['items' => [
            ['title' => 'AI Satu', 'category' => 'Bisnis', 'excerpt' => null, 'content' => 'Isi satu'],
            ['title' => 'AI Dua', 'category' => 'Opini', 'excerpt' => 'Ringkas', 'content' => 'Isi dua'],
        ]])->assertSessionHas('success', 'Berhasil menyimpan 2 artikel hasil AI sebagai draf.');

        $this->assertSame(2, Post::where('user_id', $this->member->id)->where('is_published', false)->count());
    }
}
