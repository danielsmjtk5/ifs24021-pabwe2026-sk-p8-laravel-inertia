<?php

namespace Tests\Unit\Services;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PostService(new PostRepository());
    }

    public function test_create_post_builds_slug_excerpt_and_publish_time(): void
    {
        $user = User::factory()->create();

        $draft = $this->service->createPost($user->id, [
            'title' => 'Halo Dunia',
            'category' => 'teknologi',
            'content' => "## Pembuka\n\nIni **isi** artikel.",
        ]);
        $published = $this->service->createPost($user->id, [
            'title' => 'Halo Dunia',
            'category' => 'Bisnis',
            'excerpt' => 'Ringkasan manual',
            'content' => 'Isi',
            'is_published' => true,
        ]);

        $this->assertSame('halo-dunia', $draft->slug);
        $this->assertSame('halo-dunia-2', $published->slug);
        $this->assertSame('Teknologi', $draft->category);
        $this->assertSame('Pembuka Ini isi artikel.', $draft->excerpt);
        $this->assertFalse($draft->is_published);
        $this->assertNull($draft->published_at);
        $this->assertSame('Ringkasan manual', $published->excerpt);
        $this->assertNotNull($published->published_at);
    }

    public function test_update_post_keeps_slug_unless_title_changes(): void
    {
        $post = Post::factory()->draft()->create(['title' => 'Lama', 'slug' => 'lama']);

        $this->service->updatePost($post, ['content' => 'Isi baru']);
        $this->assertSame('lama', $post->fresh()->slug);

        $this->service->updatePost($post, ['title' => 'Judul Baru', 'excerpt' => '', 'is_published' => true]);
        $fresh = $post->fresh();
        $this->assertSame('judul-baru', $fresh->slug);
        $this->assertSame('Isi baru', $fresh->excerpt);
        $this->assertTrue($fresh->is_published);
        $this->assertNotNull($fresh->published_at);
    }

    public function test_toggle_publish_keeps_original_publish_time(): void
    {
        $post = Post::factory()->draft()->create();

        $this->service->togglePublish($post);
        $firstPublishedAt = $post->fresh()->published_at;
        $this->assertTrue($post->fresh()->is_published);

        $this->service->togglePublish($post->fresh());
        $this->assertFalse($post->fresh()->is_published);
        $this->assertNull($post->fresh()->published_at);

        $this->assertNotNull($firstPublishedAt);
    }

    public function test_cover_upload_replace_and_delete(): void
    {
        Storage::fake('public');
        $post = Post::factory()->create();

        $first = $this->service->updateCoverPhoto($post, UploadedFile::fake()->image('a.jpg'));
        Storage::disk('public')->assertExists($first);

        $second = $this->service->updateCoverPhoto($post->fresh(), UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->assertTrue($this->service->deleteCoverPhoto($post->fresh()));
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($post->fresh()->cover_image_path);
    }

    public function test_delete_post_removes_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('post-covers/x.png', 'img');
        $post = Post::factory()->create(['cover_image_path' => 'post-covers/x.png']);

        $this->assertTrue($this->service->deletePost($post));
        Storage::disk('public')->assertMissing('post-covers/x.png');
    }

    public function test_slug_fallback_and_reserved_list(): void
    {
        $this->assertSame('artikel', $this->service->generateUniqueSlug('!!!'));
        $this->assertSame('judul-2', $this->service->generateUniqueSlug('Judul', null, ['judul']));
    }

    public function test_resolve_excerpt_returns_null_for_empty_content(): void
    {
        $this->assertNull($this->service->resolveExcerpt(null, ''));
    }

    public function test_listing_wrappers(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'slug' => 'publik']);
        Post::factory()->draft()->create(['user_id' => $user->id]);

        $this->assertSame(2, $this->service->getPostsForUser($user->id)->total());
        $this->assertSame(1, $this->service->getPublishedPosts()->total());
        $this->assertNotNull($this->service->getPublishedPostBySlug('publik'));
        $this->assertSame(['total' => 2, 'published' => 1, 'draft' => 1], $this->service->getStatsForUser($user->id));
    }
}
