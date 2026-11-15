<?php

namespace Tests\Feature\Admin;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_forbidden_from_admin_pages(): void
    {
        $member = User::factory()->member()->create();
        $target = User::factory()->member()->create();

        foreach (['/admin/users', "/admin/users/{$target->id}/posts", "/admin/users/{$target->id}/posts/export", '/admin/posts', '/admin/posts/export'] as $url) {
            $this->actingAs($member)->get($url)->assertForbidden();
        }
    }

    public function test_admin_can_view_and_search_users(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
        $writer = User::factory()->member()->create(['name' => 'Siti Penulis']);
        Post::factory()->count(2)->published()->create(['user_id' => $writer->id]);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserList')
                ->has('users.data', 2)
            );

        $this->actingAs($admin)->get('/admin/users?search=siti')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.posts_count', 2)
                ->where('users.data.0.published_posts_count', 2)
            );
    }

    public function test_admin_can_view_user_posts_read_only_with_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create();
        Post::factory()->published()->create(['user_id' => $target->id, 'title' => 'Terbit']);
        Post::factory()->draft()->create(['user_id' => $target->id, 'title' => 'Draf']);

        $this->actingAs($admin)->get("/admin/users/{$target->id}/posts")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserPostList')
                ->where('targetUser.id', $target->id)
                ->has('posts.data', 2)
            );

        $this->actingAs($admin)->get("/admin/users/{$target->id}/posts?status=published")
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('posts.data.0.title', 'Terbit'));
    }

    public function test_admin_can_export_user_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create(['username' => 'budi.s']);
        Post::factory()->count(2)->create(['user_id' => $target->id]);

        $response = $this->actingAs($admin)->get("/admin/users/{$target->id}/posts/export");

        $response->assertOk();
        $this->assertStringContainsString('artikel-budi_s-', $response->headers->get('content-disposition'));
    }
}
