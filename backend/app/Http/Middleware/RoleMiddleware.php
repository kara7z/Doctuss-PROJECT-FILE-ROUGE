<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($roles === [] || ! in_array($user->role, $roles, true)) {
            return new JsonResponse([
                'message' => 'Forbidden.',
            ], 403);
        }

        return $next($request);
    }
}
