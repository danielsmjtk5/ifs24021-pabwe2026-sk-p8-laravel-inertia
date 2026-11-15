<?php

namespace Tests\Feature\Post;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_published_articles(): void
    {
        $author = User::factory()->create(['name' => 'Budi']);
        Post::factory()->published()->create(['user_id' => $author->id, 'title' => 'Artikel Publik']);
        Post::factory()->draft()->create(['user_id' => $author->id, 'title' => 'Masih Draf']);

        $this->get('/articles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->where('isHome', false)
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Artikel Publik')
                ->where('posts.data.0.author.name', 'Budi')
                ->has('categories', 6)
            );
    }

    public function test_search_and_category_filter(): void
    {
        Post::factory()->published()->create(['title' => 'Belajar React', 'category' => 'Teknologi']);
        Post::factory()->published()->create(['title' => 'Investasi Pemula', 'category' => 'Bisnis']);

        $this->get('/articles?search=react')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('filters.search', 'react')
            );

        $this->get('/articles?category=Bisnis')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Investasi Pemula')
            );
    }

    public function test_guest_can_read_published_article_by_slug(): void
    {
        Post::factory()->published()->create(['slug' => 'baca-saya', 'content' => '## Halo']);

        $this->get('/articles/baca-saya')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostShow')
                ->where('post.slug', 'baca-saya')
                ->where('post.content', '## Halo')
            );
    }

    public function test_draft_or_unknown_slug_returns_404(): void
    {
        Post::factory()->draft()->create(['slug' => 'rahasia']);

        $this->get('/articles/rahasia')->assertNotFound();
        $this->get('/articles/tidak-ada')->assertNotFound();
    }
}
