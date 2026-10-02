<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class RequestContextMiddleware
{
    private const REQUEST_ID_HEADER = 'X-Request-ID';

    private const SERVICE_NAME = 'flowos-api';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->requestId($request->header(self::REQUEST_ID_HEADER));
        $startedAt = hrtime(true);

        $request->attributes->set('request_id', $requestId);
        Log::withContext([
            'service' => self::SERVICE_NAME,
            'environment' => config('app.env'),
            'request_id' => $requestId,
        ]);

        try {
            $response = $next($request);
            $response->headers->set(self::REQUEST_ID_HEADER, $requestId);

            Log::info('Request completed.', $this->requestContext($request, $response, $startedAt));

            return $response;
        } finally {
            // Long-running runtimes and workers must not inherit request context.
            Log::withoutContext();
        }
    }

    private function requestId(?string $candidate): string
    {
        if ($candidate !== null && strlen($candidate) <= 64 && Uuid::isValid($candidate)) {
            return $candidate;
        }

        return Uuid::uuid7()->toString();
    }

    /**
     * @return array<string, int|string>
     */
    private function requestContext(Request $request, Response $response, int $startedAt): array
    {
        $context = [
            'event' => 'request.completed',
            'method' => $request->method(),
            'route' => $request->route()?->getName() ?? $request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
        ];

        if ($organizationId = $request->attributes->get('organization_id')) {
            $context['organization_id'] = $organizationId;
        }

        if ($userId = $request->user()?->getAuthIdentifier()) {
            $context['user_id'] = $userId;
        }

        return $context;
    }
}
