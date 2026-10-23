<?php

use App\Http\Controllers\Api\V1\AuditEventController;
use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\MfaController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CatalogueController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\InventoryLocationController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\MembershipController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\StockController;
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

    // The multi-factor challenge is redeemed for the credential it was issued
    // for, so it is exchanged outside the authenticated group.
    Route::post('/auth/mfa/verify', [MfaController::class, 'verify'])->middleware('throttle:mfa-verify');

    Route::middleware(['auth:sanctum', 'active-user', 'throttle:api-standard'])->group(function () {
        Route::get('/auth/me', CurrentUserController::class);
        Route::post('/auth/logout', [SessionController::class, 'destroy']);
        Route::post('/auth/logout-all', [SessionController::class, 'destroyAll']);
        Route::post('/auth/email/verification-notification', [EmailVerificationController::class, 'sendNotification'])->middleware('throttle:auth-verification-resend');
        Route::post('/auth/mfa/enroll', [MfaController::class, 'enroll'])->middleware(['verified-writes', 'throttle:mfa-enrollment']);
        Route::post('/auth/mfa/confirm', [MfaController::class, 'confirm'])->middleware(['verified-writes', 'throttle:mfa-enrollment']);
        Route::post('/auth/mfa/disable', [MfaController::class, 'disable'])->middleware(['verified-writes', 'throttle:mfa-enrollment']);
        Route::get('/invitations/{token}', [InvitationController::class, 'inspect']);
        Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);
        Route::post('/invitations/{token}/decline', [InvitationController::class, 'decline']);

        Route::get('/devices', [DeviceController::class, 'index']);
        Route::post('/devices', [DeviceController::class, 'store'])->middleware('verified-writes');
        Route::post('/devices/{device}/revoke', [DeviceController::class, 'revoke']);

        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
        Route::get('/organizations/{organization}/invitations', [InvitationController::class, 'index']);
        Route::get('/organizations/{organization}/memberships', [MembershipController::class, 'index']);
        Route::get('/organizations/{organization}/memberships/{membership}', [MembershipController::class, 'show']);
        Route::get('/organizations/{organization}/permissions', [RoleController::class, 'permissions']);
        Route::get('/organizations/{organization}/roles', [RoleController::class, 'index']);
        Route::get('/organizations/{organization}/roles/{role}', [RoleController::class, 'show']);
        Route::get('/organizations/{organization}/me/permissions', [RoleController::class, 'effective']);
        Route::get('/organizations/{organization}/audit-events', [AuditEventController::class, 'index']);

        Route::middleware(['verified-writes'])->group(function () {
            Route::post('/organizations', [OrganizationController::class, 'store'])->middleware('idempotency');
            Route::patch('/organizations/{organization}', [OrganizationController::class, 'update']);
            Route::post('/organizations/{organization}/suspend', [OrganizationController::class, 'suspend']);
            Route::post('/organizations/{organization}/activate', [OrganizationController::class, 'activate']);
            Route::post('/organizations/{organization}/close', [OrganizationController::class, 'close']);
            Route::post('/organizations/{organization}/invitations', [InvitationController::class, 'store'])->middleware('idempotency');
            Route::post('/organizations/{organization}/invitations/{invitation}/resend', [InvitationController::class, 'resend']);
            Route::post('/organizations/{organization}/invitations/{invitation}/cancel', [InvitationController::class, 'cancel']);
            Route::post('/organizations/{organization}/memberships/{membership}/suspend', [MembershipController::class, 'suspend']);
            Route::post('/organizations/{organization}/memberships/{membership}/activate', [MembershipController::class, 'activate']);
            Route::post('/organizations/{organization}/memberships/{membership}/revoke', [MembershipController::class, 'revoke']);
            Route::post('/organizations/{organization}/roles', [RoleController::class, 'store']);
            Route::patch('/organizations/{organization}/roles/{role}', [RoleController::class, 'update']);
            Route::delete('/organizations/{organization}/roles/{role}', [RoleController::class, 'destroy']);
            Route::put('/organizations/{organization}/memberships/{membership}/roles', [RoleController::class, 'assign']);
        });

        Route::prefix('organizations/{organization}')->middleware('tenant')->group(function () {
            Route::get('/branches', [BranchController::class, 'index']);
            Route::get('/branches/{branch}', [BranchController::class, 'show']);

            Route::middleware(['verified-writes'])->group(function () {
                Route::post('/branches', [BranchController::class, 'store']);
                Route::patch('/branches/{branch}', [BranchController::class, 'update']);
                Route::post('/branches/{branch}/activate', [BranchController::class, 'activate']);
                Route::post('/branches/{branch}/deactivate', [BranchController::class, 'deactivate']);
                Route::post('/branches/{branch}/close', [BranchController::class, 'close']);
                Route::post('/branches/{branch}/make-primary', [BranchController::class, 'makePrimary']);
            });
        });

        // Catalogue and inventory. The organization in the path is the scope
        // and the permission check is the authority.
        Route::prefix('organizations/{organization}')->group(function () {
            Route::get('/products', [ProductController::class, 'index']);
            Route::get('/products/{product}', [ProductController::class, 'show']);
            Route::get('/categories', [CatalogueController::class, 'listCategories']);
            Route::get('/unit-of-measures', [CatalogueController::class, 'listUnitOfMeasures']);
            Route::get('/inventory-locations', [InventoryLocationController::class, 'index']);
            Route::get('/inventory-locations/{location}', [InventoryLocationController::class, 'show']);
            Route::get('/stock-movements', [StockController::class, 'indexMovements']);
            Route::get('/stock-balances', [StockController::class, 'indexBalances']);

            Route::middleware(['verified-writes'])->group(function () {
                Route::post('/products', [ProductController::class, 'store']);
                Route::patch('/products/{product}', [ProductController::class, 'update']);
                Route::post('/products/{product}/archive', [ProductController::class, 'archive']);
                Route::post('/products/{product}/restore', [ProductController::class, 'restore']);
                Route::post('/categories', [CatalogueController::class, 'storeCategory']);
                Route::patch('/categories/{category}', [CatalogueController::class, 'updateCategory']);
                Route::post('/unit-of-measures', [CatalogueController::class, 'storeUnitOfMeasure']);
                Route::post('/inventory-locations', [InventoryLocationController::class, 'store']);
                Route::patch('/inventory-locations/{location}', [InventoryLocationController::class, 'update']);
                Route::post('/inventory-locations/{location}/archive', [InventoryLocationController::class, 'archive']);
                Route::post('/stock-movements', [StockController::class, 'storeMovement'])->middleware('idempotency');
                Route::post('/stock-counts', [StockController::class, 'storeCount'])->middleware('idempotency');
                Route::post('/stock-transfers', [StockController::class, 'storeTransfer'])->middleware('idempotency');
            });
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
