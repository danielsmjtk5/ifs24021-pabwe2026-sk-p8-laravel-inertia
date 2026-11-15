<?php

namespace App\Http\Middleware;

use App\Features\Post\Models\Post;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePostOwnership
{
    /**
     * Stop members from reaching other authors' data, and stop anyone except the
     * author from changing a post (write methods: POST, PUT, PATCH, DELETE).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentUser = $request->user();

        if (!$currentUser) {
            abort(401, 'Unauthenticated');
        }

        $routeUser = $request->route('user');
        if ($routeUser) {
            $targetUserId = $routeUser instanceof User ? $routeUser->id : (int) $routeUser;

            if ($currentUser->isMember() && $currentUser->id !== $targetUserId) {
                abort(403, 'Akses ditolak: Anda tidak dapat mengakses artikel penulis lain.');
            }
        }

        $routePost = $request->route('post');
        if ($routePost) {
            $post = $routePost instanceof Post ? $routePost : Post::find($routePost);

            if ($post) {
                if ($currentUser->isMember() && $currentUser->id !== $post->user_id) {
                    abort(403, 'Akses ditolak: Anda tidak dapat mengakses artikel penulis lain.');
                }

                if (!$request->isMethodSafe() && $currentUser->id !== $post->user_id) {
                    abort(403, 'Akses ditolak: Hanya penulis yang dapat mengubah artikel ini.');
                }
            }
        }

        return $next($request);
    }
}
