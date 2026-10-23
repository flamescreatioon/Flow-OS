<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Psr\Log\LogLevel;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function report(Throwable $e): void
    {
        $request = request();

        if ($request instanceof Request && $requestId = $request->attributes->get('request_id')) {
            Log::withContext(['request_id' => $requestId]);
        }

        try {
            parent::report($e);
        } finally {
            Log::withoutContext();
        }
    }

    public function render($request, Throwable $e)
    {
        if ($request instanceof Request && $request->is('api/*')) {
            if ($e instanceof ValidationException) {
                return $this->apiError($request, ['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'The given data was invalid.', 'details' => ['fields' => $e->errors()]]], 422);
            }
            if ($e instanceof AuthenticationException) {
                return $this->apiError($request, ['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Unauthenticated.']], 401);
            }
            if ($e instanceof AuthorizationException) {
                return $this->apiError($request, ['error' => ['code' => 'FORBIDDEN', 'message' => 'This action is unauthorized.']], 403);
            }
            if ($e instanceof ModelNotFoundException) {
                return $this->apiError($request, ['error' => ['code' => 'RESOURCE_NOT_FOUND', 'message' => 'Resource not found.']], 404);
            }
            if ($e instanceof ThrottleRequestsException) {
                return $this->apiError($request, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'Too many requests.']], 429);
            }

            if (! config('app.debug')) {
                return $this->apiError($request, ['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'An unexpected error occurred.']], 500);
            }
        }

        return parent::render($request, $e);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function apiError(Request $request, array $payload, int $status)
    {
        $response = response()->json($payload, $status);

        if ($requestId = $request->attributes->get('request_id')) {
            $response->headers->set('X-Request-ID', $requestId);
        }

        return $response;
    }
}
