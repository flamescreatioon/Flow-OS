<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
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

    public function render($request, Throwable $e)
    {
        if ($request instanceof Request && $request->is('api/*')) {
            if ($e instanceof ValidationException) {
                return response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'The given data was invalid.', 'details' => ['fields' => $e->errors()]]], 422);
            }
            if ($e instanceof AuthenticationException) {
                return response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Unauthenticated.']], 401);
            }
            if ($e instanceof AuthorizationException) {
                return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'This action is unauthorized.']], 403);
            }
            if ($e instanceof ModelNotFoundException) {
                return response()->json(['error' => ['code' => 'RESOURCE_NOT_FOUND', 'message' => 'Resource not found.']], 404);
            }

            if (! config('app.debug')) {
                return response()->json(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'An unexpected error occurred.']], 500);
            }
        }

        return parent::render($request, $e);
    }
}
