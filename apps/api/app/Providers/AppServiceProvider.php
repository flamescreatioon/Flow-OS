<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Spatie\Multitenancy\Multitenancy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Sanctum 4 only publishes its migrations; this application owns the
        // personal access token schema through its own migration.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->app->scoped(TenantContext::class);

        $this->app->alias(Multitenancy::class, 'multitenancy');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
