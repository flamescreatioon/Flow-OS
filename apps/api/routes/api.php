<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'flowos-api',
        ]);
    })->name('health');

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
