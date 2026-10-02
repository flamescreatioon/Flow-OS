<?php

namespace App\Providers;

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
        Sanctum::ignoreMigrations();

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
