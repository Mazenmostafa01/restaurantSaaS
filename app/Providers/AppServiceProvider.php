<?php

namespace App\Providers;

use App\Services\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton: the same TenantContext instance is shared across the entire
        // request — middleware sets it, TenantScope and BelongsToTenant read it.
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
