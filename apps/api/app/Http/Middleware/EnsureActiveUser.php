<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->status !== UserStatus::Active) {
            return response()->json([
                'error' => [
                    'code' => 'ACCOUNT_INACTIVE',
                    'message' => 'This account is not active.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
