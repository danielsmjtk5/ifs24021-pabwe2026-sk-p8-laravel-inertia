<?php

namespace Tests\Feature\Post;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminPostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_monitors_all_posts_with_filters(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->published()->create(['title' => 'Teknologi Masa Depan', 'category' => 'Teknologi']);
        Post::factory()->draft()->create(['title' => 'Catatan Bisnis', 'category' => 'Bisnis']);

        $this->actingAs($admin)->get('/admin/posts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PostList')
                ->has('posts.data', 2)
                ->has('posts.data.0.user.name')
            );

        $this->actingAs($admin)->get('/admin/posts?category=Bisnis&status=draft&search=catatan')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Catatan Bisnis')
            );
    }

    public function test_admin_exports_every_post(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/admin/posts/export');

        $response->assertOk();
        $this->assertStringContainsString('semua-artikel-', $response->headers->get('content-disposition'));
    }

    public function test_admin_cannot_modify_any_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        // Writer routes are member-only, so admin is stopped before reaching the post.
        $this->actingAs($admin)->put("/dashboard/posts/{$post->id}", ['title' => 'x'])->assertForbidden();
        $this->actingAs($admin)->delete("/dashboard/posts/{$post->id}")->assertForbidden();
    }
}
