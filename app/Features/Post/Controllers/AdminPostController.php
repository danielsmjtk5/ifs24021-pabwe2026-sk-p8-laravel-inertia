<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Services\PostExcelService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPostController
{
    /**
     * Read-only monitoring of every post in the system.
     */
    public function index(Request $request, PostRepositoryInterface $repository): Response
    {
        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $category = (string) $request->query('category', '');

        return Inertia::render('Admin/PostList', [
            'posts' => $repository->allPaginated($status ?: null, $search ?: null, $category ?: null, 15),
            'categories' => PostCategory::options(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $category,
            ],
        ]);
    }

    public function export(PostRepositoryInterface $repository, PostExcelService $excelService): StreamedResponse
    {
        return $excelService->exportToResponse($repository->allWithAuthor(), 'semua-artikel-'.now()->format('Ymd-His').'.xlsx');
    }
}
