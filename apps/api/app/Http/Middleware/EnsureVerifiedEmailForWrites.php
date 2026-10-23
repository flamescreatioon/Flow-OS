<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedEmailForWrites
{
    /**
     * Require verified email for state-changing tenant operations.
     * Reads stay accessible so users can complete verification.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('get') || $request->isMethod('head') || $request->isMethod('options')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! $user->hasVerifiedEmail()) {
            return response()->json([
                'error' => [
                    'code' => 'EMAIL_NOT_VERIFIED',
                    'message' => 'Verify your email address before making changes.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
