<?php

namespace Tests\Unit\Middleware;

use App\Features\Post\Models\Post;
use App\Http\Middleware\EnsurePostOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsurePostOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function check(?User $user, string $method, array $params): int
    {
        $request = Request::create('/x', $method);
        $request->setUserResolver(fn () => $user);
        $route = new Route($method, '/x', []);
        $route->bind($request);
        foreach ($params as $key => $value) {
            $route->setParameter($key, $value);
        }
        $request->setRouteResolver(fn () => $route);

        try {
            return (new EnsurePostOwnership())->handle($request, fn () => new Response('OK'))->getStatusCode();
        } catch (HttpException $e) {
            return $e->getStatusCode();
        }
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->assertSame(401, $this->check(null, 'GET', []));
    }

    public function test_user_route_parameter(): void
    {
        $member = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $this->assertSame(200, $this->check($member, 'GET', ['user' => $member]));
        $this->assertSame(403, $this->check($member, 'GET', ['user' => $other->id]));
        $this->assertSame(200, $this->check($admin, 'GET', ['user' => $other]));
    }

    public function test_post_route_parameter(): void
    {
        $owner = User::factory()->member()->create();
        $other = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $this->assertSame(200, $this->check($owner, 'PUT', ['post' => $post]));
        $this->assertSame(403, $this->check($other, 'GET', ['post' => $post->id]));
        // Admin may read but never write
        $this->assertSame(200, $this->check($admin, 'GET', ['post' => $post]));
        $this->assertSame(403, $this->check($admin, 'DELETE', ['post' => $post]));
        // Unknown id: let the route return 404 itself
        $this->assertSame(200, $this->check($owner, 'GET', ['post' => 999999]));
    }
}
