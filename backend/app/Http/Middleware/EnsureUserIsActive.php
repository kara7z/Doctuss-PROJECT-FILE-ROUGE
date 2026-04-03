<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status === UserStatus::SUSPENDED) {
            $request->user()->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Your account has been suspended. You have been logged out.',
            ], 403);
        }

        return $next($request);
    }
}

