<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_renders_public_gallery_for_guest(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->has('posts')
                ->where('isHome', true)
            );
    }

    public function test_root_renders_public_gallery_for_member(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/PostIndex'));
    }

    public function test_logged_in_user_is_redirected_away_from_login(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->get('/login')->assertRedirect();
    }

    public function test_guest_is_redirected_to_login_for_dashboard(): void
    {
        $this->get('/dashboard/posts')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_health_check_endpoint(): void
    {
        $this->get('/up')->assertOk();
    }
}
