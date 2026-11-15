<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPostController
{
    /**
     * Public article gallery for "/" and "/articles" (no login needed).
     */
    public function index(Request $request, PostService $service): Response
    {
        $search = (string) $request->query('search', '');
        $category = (string) $request->query('category', '');

        $posts = $service->getPublishedPosts($search ?: null, $category ?: null, 9)
            ->through(fn (Post $post) => $this->presentCard($post));

        return Inertia::render('Public/PostIndex', [
            'posts' => $posts,
            'categories' => PostCategory::options(),
            'filters' => [
                'search' => $search,
                'category' => $category,
            ],
            'isHome' => $request->routeIs('home'),
        ]);
    }

    /**
     * Read a single published article by slug. Drafts and unknown slugs return 404.
     */
    public function show(string $slug, PostService $service): Response
    {
        $post = $service->getPublishedPostBySlug($slug);
        abort_if($post === null, 404);

        return Inertia::render('Public/PostShow', [
            'post' => [
                ...$this->presentCard($post),
                'content' => $post->content,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentCard(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'category' => $post->category,
            'excerpt' => $post->excerpt,
            'cover_image_url' => $post->coverImageUrl(),
            'reading_minutes' => $post->readingMinutes(),
            'published_at' => $post->published_at?->format('d M Y'),
            'author' => [
                'name' => $post->user?->name,
                'username' => $post->user?->username,
            ],
        ];
    }
}
