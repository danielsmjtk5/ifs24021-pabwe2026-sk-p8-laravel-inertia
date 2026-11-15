<?php

namespace Tests\Unit\Models;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($post->user->is($user));
    }

    public function test_casts_and_factory_states(): void
    {
        $published = Post::factory()->published()->create();
        $draft = Post::factory()->draft()->create();

        $this->assertTrue($published->is_published);
        $this->assertInstanceOf(Carbon::class, $published->published_at);
        $this->assertFalse($draft->is_published);
        $this->assertNull($draft->published_at);
    }

    public function test_cover_image_url_returns_url_or_null(): void
    {
        Storage::fake('public');
        $post = Post::factory()->create(['cover_image_path' => null]);
        $this->assertNull($post->coverImageUrl());

        $post->cover_image_path = 'post-covers/missing.png';
        $this->assertNull($post->coverImageUrl());

        Storage::disk('public')->put('post-covers/a.png', 'img');
        $post->cover_image_path = 'post-covers/a.png';
        $this->assertStringContainsString('post-covers/a.png', $post->coverImageUrl());
    }

    public function test_reading_minutes(): void
    {
        $short = Post::factory()->make(['content' => 'satu dua tiga']);
        $long = Post::factory()->make(['content' => str_repeat('kata ', 450)]);

        $this->assertSame(1, $short->readingMinutes());
        $this->assertSame(3, $long->readingMinutes());
    }
}
