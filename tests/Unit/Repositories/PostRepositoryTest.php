<?php

namespace Tests\Unit\Repositories;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PostRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new PostRepository();
    }

    public function test_for_user_filters_by_status_search_and_category(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Belajar LARAVEL', 'category' => 'Teknologi']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Resep Kopi', 'category' => 'Gaya Hidup', 'content' => 'laravel di dapur']);
        Post::factory()->published()->create(['title' => 'Laravel milik orang lain']);

        $this->assertSame(2, $this->repo->forUser($user->id)->total());
        $this->assertSame(1, $this->repo->forUser($user->id, 'published')->total());
        $this->assertSame(1, $this->repo->forUser($user->id, 'draft')->total());
        $this->assertSame(2, $this->repo->forUser($user->id, 'unknown-status')->total());
        // Case-insensitive search on title OR content
        $this->assertSame(2, $this->repo->forUser($user->id, null, 'laravel')->total());
        $this->assertSame(1, $this->repo->forUser($user->id, null, null, 'Gaya Hidup')->total());
    }

    public function test_all_paginated_includes_every_author(): void
    {
        Post::factory()->count(3)->create();

        $page = $this->repo->allPaginated(perPage: 2);

        $this->assertSame(3, $page->total());
        $this->assertTrue($page->first()->relationLoaded('user'));
    }

    public function test_published_paginated_and_find_by_slug_hide_drafts(): void
    {
        $published = Post::factory()->published()->create(['slug' => 'terbit']);
        Post::factory()->draft()->create(['slug' => 'draf']);

        $this->assertSame(1, $this->repo->publishedPaginated()->total());
        $this->assertTrue($this->repo->findPublishedBySlug('terbit')->is($published));
        $this->assertNull($this->repo->findPublishedBySlug('draf'));
        $this->assertNull($this->repo->findPublishedBySlug('tidak-ada'));
    }

    public function test_all_for_user_and_all_with_author(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);
        Post::factory()->create();

        $this->assertCount(2, $this->repo->allForUser($user->id));
        $this->assertCount(3, $this->repo->allWithAuthor());
    }

    public function test_create_update_delete(): void
    {
        $user = User::factory()->create();
        $post = $this->repo->create([
            'user_id' => $user->id,
            'title' => 'A',
            'slug' => 'a',
            'category' => 'Umum',
            'content' => 'isi',
        ]);

        $this->assertTrue($this->repo->update($post, ['title' => 'B']));
        $this->assertSame('B', $post->fresh()->title);
        $this->assertTrue($this->repo->delete($post));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_insert_many_and_slug_exists(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($this->repo->insertMany([]));
        $this->assertTrue($this->repo->insertMany([
            ['user_id' => $user->id, 'title' => 'X', 'slug' => 'x', 'category' => 'Umum', 'content' => '-', 'is_published' => false],
        ]));

        $id = Post::where('slug', 'x')->value('id');
        $this->assertTrue($this->repo->slugExists('x'));
        $this->assertFalse($this->repo->slugExists('x', $id));
        $this->assertFalse($this->repo->slugExists('y'));
    }

    public function test_stats_for_user(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->published()->create(['user_id' => $user->id]);
        Post::factory()->count(3)->draft()->create(['user_id' => $user->id]);

        $this->assertSame(['total' => 5, 'published' => 2, 'draft' => 3], $this->repo->getStatsForUser($user->id));
        $this->assertSame(['total' => 0, 'published' => 0, 'draft' => 0], $this->repo->getStatsForUser(999));
    }
}
