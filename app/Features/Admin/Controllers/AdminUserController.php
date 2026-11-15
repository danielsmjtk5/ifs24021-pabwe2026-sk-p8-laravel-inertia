<?php

namespace App\Features\Admin\Controllers;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Services\PostExcelService;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController
{
    public function index(Request $request, AdminUserService $adminService): Response
    {
        $search = (string) $request->query('search', '');

        return Inertia::render('Admin/UserList', [
            'users' => $adminService->getUsersList($search ?: null, 10),
            'filters' => ['search' => $search],
        ]);
    }

    public function posts(Request $request, User $user, AdminUserService $adminService): Response
    {
        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $category = (string) $request->query('category', '');

        return Inertia::render('Admin/UserPostList', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'posts' => $adminService->getUserPosts($user->id, $status ?: null, $search ?: null, $category ?: null, 10),
            'categories' => PostCategory::options(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $category,
            ],
        ]);
    }

    public function exportUserPosts(User $user, PostRepositoryInterface $repository, PostExcelService $excelService): StreamedResponse
    {
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $user->username);

        return $excelService->exportToResponse(
            $repository->allForUser($user->id),
            "artikel-{$safeName}-".now()->format('Ymd-His').'.xlsx'
        );
    }
}
