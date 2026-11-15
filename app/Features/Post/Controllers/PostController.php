<?php

namespace App\Features\Post\Controllers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController
{
    public function index(Request $request, PostService $service): Response
    {
        $userId = (int) Auth::id();
        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $category = (string) $request->query('category', '');

        return Inertia::render('Post/Index', [
            'posts' => $service->getPostsForUser($userId, $status ?: null, $search ?: null, $category ?: null, 10),
            'stats' => $service->getStatsForUser($userId),
            'categories' => PostCategory::options(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $category,
            ],
        ]);
    }

    public function store(Request $request, PostService $service): RedirectResponse
    {
        $service->createPost((int) Auth::id(), $this->validatePost($request));

        return back()->with('success', 'Artikel baru berhasil disimpan.');
    }

    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        return Inertia::render('Post/Show', [
            'post' => $this->present($post),
            'categories' => PostCategory::options(),
        ]);
    }

    public function update(Request $request, Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->updatePost($post, $this->validatePost($request));

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $service->deletePost($post);

        return redirect()->route('dashboard.posts.index')->with('success', 'Artikel berhasil dihapus.');
    }

    public function togglePublish(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->togglePublish($post);
        $message = $post->is_published ? 'Artikel berhasil dipublikasikan.' : 'Artikel dikembalikan menjadi draf.';

        return back()->with('success', $message);
    }

    public function updateCover(Request $request, Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $request->validate([
            'coverPhoto' => ['required', 'image', 'max:5120'],
        ]);

        $service->updateCoverPhoto($post, $request->file('coverPhoto'));

        return back()->with('success', 'Foto sampul berhasil diperbarui.');
    }

    public function deleteCover(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->deleteCoverPhoto($post);

        return back()->with('success', 'Foto sampul berhasil dihapus.');
    }

    public function exportExcel(PostRepositoryInterface $repository, PostExcelService $excelService): StreamedResponse
    {
        $posts = $repository->allForUser((int) Auth::id());

        return $excelService->exportToResponse($posts, 'artikel-saya-'.now()->format('Ymd-His').'.xlsx');
    }

    public function importExcel(Request $request, PostExcelService $excelService): RedirectResponse
    {
        $request->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = $excelService->import($request->file('importFile')->getRealPath(), (int) Auth::id());

        return back()->with('success', "Berhasil mengimpor {$result['imported']} artikel.");
    }

    public function generateAi(Request $request, AiClientInterface $aiClient): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:1000'],
            'count' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'prompt.required' => 'Topik artikel wajib diisi minimal 3 karakter.',
            'prompt.min' => 'Topik artikel wajib diisi minimal 3 karakter.',
            'count.min' => 'Jumlah artikel minimal 1.',
            'count.max' => 'Jumlah artikel maksimal 5.',
        ]);

        try {
            $items = $aiClient->generatePosts($validated['prompt'], (int) $validated['count']);

            return response()->json([
                'success' => true,
                'items' => array_map(fn (GeneratedPostItem $item) => $item->toArray(), $items),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat artikel dengan AI: '.$e->getMessage(),
            ], 422);
        }
    }

    public function saveAi(Request $request, PostService $service): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:5'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string'],
            'items.*.excerpt' => ['nullable', 'string'],
            'items.*.content' => ['required', 'string'],
        ]);

        foreach ($validated['items'] as $item) {
            $service->createPost((int) Auth::id(), [...$item, 'is_published' => false]);
        }

        $count = count($validated['items']);

        return back()->with('success', "Berhasil menyimpan {$count} artikel hasil AI sebagai draf.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePost(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(PostCategory::values())],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'is_published' => ['boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'category' => $post->category,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'is_published' => $post->is_published,
            'cover_image_url' => $post->coverImageUrl(),
            'reading_minutes' => $post->readingMinutes(),
            'published_at' => $post->published_at?->format('d M Y H:i'),
            'created_at' => $post->created_at?->format('d M Y H:i'),
            'updated_at' => $post->updated_at?->format('d M Y H:i'),
        ];
    }
}
