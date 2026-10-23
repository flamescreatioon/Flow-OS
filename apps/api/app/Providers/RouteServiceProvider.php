<?php

namespace App\Providers;

use App\Models\Device;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        parent::boot();

        Route::model('membership', OrganizationMembership::class);
        Route::model('invitation', OrganizationInvitation::class);
        Route::model('device', Device::class);

        RateLimiter::for('auth-login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip())->response(function (Request $request) {
            return response()->json(['error' => ['code' => 'RATE_LIMITED', 'message' => 'Too many requests.']], 429);
        }));
        RateLimiter::for('auth-token', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip())->response(function (Request $request) {
            return response()->json(['error' => ['code' => 'RATE_LIMITED', 'message' => 'Too many requests.']], 429);
        }));
        RateLimiter::for('api-standard', fn (Request $request) => Limit::perMinute(60)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()))->response(function (Request $request) {
            return response()->json(['error' => ['code' => 'RATE_LIMITED', 'message' => 'Too many requests.']], 429);
        }));
        RateLimiter::for('auth-password-reset', fn (Request $request) => Limit::perMinute(3)->by(strtolower((string) $request->input('email')).'|'.$request->ip())->response(function (Request $request) {
            return response()->json(['error' => ['code' => 'RATE_LIMITED', 'message' => 'Too many requests.']], 429);
        }));
        RateLimiter::for('auth-verification-resend', fn (Request $request) => Limit::perMinute(3)->by((string) $request->user()?->getAuthIdentifier())->response(function (Request $request) {
            return response()->json(['error' => ['code' => 'RATE_LIMITED', 'message' => 'Too many requests.']], 429);
        }));
        RateLimiter::for('mfa-verify', fn (Request $request) => Limit::perMinute(10)->by((string) ($request->ip() ?? 'unknown'))->response(function (Request $request) {
            return response()->json(['error' => ['code' => 'RATE_LIMITED', 'message' => 'Too many requests.']], 429);
        }));
        RateLimiter::for('mfa-enrollment', fn (Request $request) => Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()))->response(function (Request $request) {
            return response()->json(['error' => ['code' => 'RATE_LIMITED', 'message' => 'Too many requests.']], 429);
        }));

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
