<?php

use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'flowos-api',
        ]);
    })->name('health');

    Route::post('/auth/register', RegisterController::class)->middleware('throttle:auth-login');
    Route::post('/auth/login', [SessionController::class, 'store'])->middleware('throttle:auth-login');
    Route::post('/auth/token', [TokenController::class, 'store'])->middleware('throttle:auth-token');
    Route::post('/auth/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:auth-password-reset');
    Route::post('/auth/reset-password', [PasswordResetController::class, 'reset']);
    Route::get('/auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::middleware(['auth:sanctum', 'active-user'])->group(function () {
        Route::get('/auth/me', CurrentUserController::class);
        Route::post('/auth/logout', [SessionController::class, 'destroy']);
        Route::post('/auth/logout-all', [SessionController::class, 'destroyAll']);
        Route::post('/auth/email/verification-notification', [EmailVerificationController::class, 'sendNotification'])->middleware('throttle:auth-verification-resend');

        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
        Route::patch('/organizations/{organization}', [OrganizationController::class, 'update']);
        Route::post('/organizations/{organization}/suspend', [OrganizationController::class, 'suspend']);
        Route::post('/organizations/{organization}/activate', [OrganizationController::class, 'activate']);
        Route::post('/organizations/{organization}/close', [OrganizationController::class, 'close']);

        Route::prefix('organizations/{organization}')->middleware('tenant')->group(function () {
            Route::get('/branches', [BranchController::class, 'index']);
            Route::post('/branches', [BranchController::class, 'store']);
            Route::get('/branches/{branch}', [BranchController::class, 'show']);
            Route::patch('/branches/{branch}', [BranchController::class, 'update']);
            Route::post('/branches/{branch}/activate', [BranchController::class, 'activate']);
            Route::post('/branches/{branch}/deactivate', [BranchController::class, 'deactivate']);
            Route::post('/branches/{branch}/close', [BranchController::class, 'close']);
            Route::post('/branches/{branch}/make-primary', [BranchController::class, 'makePrimary']);
        });
    });

    // API routes will be added here
    // Route::apiResource('organizations', OrganizationController::class);
    // Route::apiResource('branches', BranchController::class);
    // etc.
});

Route::fallback(function () {
    return response()->json(['error' => [
        'code' => 'RESOURCE_NOT_FOUND',
        'message' => 'Resource not found.',
    ]], 404);
});
