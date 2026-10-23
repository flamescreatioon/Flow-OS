<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class IdempotencyKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! $key || strlen($key) > 100) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $organizationId = $request->route('organization')?->getKey();
        $route = $request->route()?->getName() ?? $request->path();
        $hash = hash('sha256', $request->getContent() ?: json_encode($request->all()));

        // The key is reserved before the request runs so that concurrent
        // requests carrying the same key cannot both execute the operation.
        $reserved = $this->reserve($user, $organizationId, $route, $key, $hash);

        if ($reserved instanceof Response) {
            return $reserved;
        }

        try {
            /** @var Response $response */
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->release($user, $organizationId, $route, $key);

            throw $exception;
        }

        if ($response instanceof JsonResponse) {
            $response->headers->set('Idempotent-Replayed', 'false');
        }

        if ($response->getStatusCode() < 500) {
            $this->complete($user, $organizationId, $route, $key, $hash, $response);
        } else {
            $this->release($user, $organizationId, $route, $key);
        }

        return $response;
    }

    /**
     * Reserve the idempotency key for the current request.
     *
     * Returns a response to short-circuit the request with, or null when the
     * caller owns the reservation and may execute the request.
     */
    protected function reserve(User $user, ?string $organizationId, string $route, string $key, string $hash): ?Response
    {
        try {
            return DB::transaction(function () use ($user, $organizationId, $route, $key, $hash) {
                $existing = $this->query($user, $organizationId, $route, $key)->lockForUpdate()->first();

                if ($existing && ! $existing->isExpired()) {
                    return $this->replay($existing, $hash);
                }

                if ($existing) {
                    // An expired reservation is renewed in place so that the
                    // partial unique indexes are never violated.
                    $existing->forceFill([
                        'request_hash' => $hash,
                        'status' => 'processing',
                        'response_code' => 0,
                        'response_body' => [],
                        'expires_at' => now()->addDay(),
                    ])->save();

                    return null;
                }

                IdempotencyKey::create([
                    'user_id' => $user->getKey(),
                    'organization_id' => $organizationId,
                    'route' => $route,
                    'key' => $key,
                    'request_hash' => $hash,
                    'status' => 'processing',
                    'response_code' => 0,
                    'response_body' => [],
                    'expires_at' => now()->addDay(),
                ]);

                return null;
            });
        } catch (UniqueConstraintViolationException) {
            // Another request reserved the key first. Its reservation is the
            // authoritative one, so the response is replayed from it.
            $existing = $this->query($user, $organizationId, $route, $key)->first();

            if ($existing) {
                return $this->replay($existing, $hash);
            }

            return $this->conflict('The idempotency key is being processed by another request.');
        }
    }

    /**
     * Record the response for a reserved key so later requests can replay it.
     */
    protected function complete(User $user, ?string $organizationId, string $route, string $key, string $hash, Response $response): void
    {
        DB::transaction(function () use ($user, $organizationId, $route, $key, $hash, $response) {
            $existing = $this->query($user, $organizationId, $route, $key)->lockForUpdate()->first();

            if (! $existing) {
                return;
            }

            $existing->forceFill([
                'request_hash' => $hash,
                'status' => 'completed',
                'response_code' => $response->getStatusCode(),
                'response_body' => json_decode($response->getContent() ?: '{}', true) ?? [],
                'expires_at' => now()->addDay(),
            ])->save();
        });
    }

    /**
     * Drop a reservation that produced no usable response so that the key can
     * be retried.
     */
    protected function release(User $user, ?string $organizationId, string $route, string $key): void
    {
        try {
            $this->query($user, $organizationId, $route, $key)
                ->where('status', 'processing')
                ->delete();
        } catch (Throwable) {
            // Releasing the reservation is best effort. The reservation
            // expires on its own if it cannot be released here.
        }
    }

    protected function replay(IdempotencyKey $existing, string $hash): Response
    {
        if (! hash_equals((string) $existing->request_hash, $hash)) {
            return $this->conflict('Idempotency key was already used with a different payload.');
        }

        if ($existing->isProcessing()) {
            return $this->conflict('The idempotency key is being processed by another request.');
        }

        $replay = response()->json($existing->response_body, $existing->response_code);
        $replay->headers->set('Idempotent-Replayed', 'true');

        return $replay;
    }

    protected function conflict(string $message): Response
    {
        return response()->json([
            'error' => [
                'code' => 'IDEMPOTENCY_CONFLICT',
                'message' => $message,
            ],
        ], 409);
    }

    protected function query(User $user, ?string $organizationId, string $route, string $key): Builder
    {
        return IdempotencyKey::query()
            ->where('user_id', $user->getKey())
            ->where('route', $route)
            ->where('key', $key)
            ->where(fn ($query) => $organizationId !== null
                ? $query->where('organization_id', $organizationId)
                : $query->whereNull('organization_id'));
    }
}
