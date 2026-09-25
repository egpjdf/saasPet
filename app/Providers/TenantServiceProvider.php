<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Tenant\TenantContext;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext();
        });

        $this->app->alias(TenantContext::class, 'tenant');
    }

    public function boot(): void
    {
        // Registra middleware global para APIs
        $this->app['router']->pushMiddlewareToGroup('api', 'tenant.org');
        $this->app['router']->pushMiddlewareToGroup('api', 'tenant.ws');
        $this->app['router']->pushMiddlewareToGroup('api', 'tenant.access');
        $this->app['router']->pushMiddlewareToGroup('api', 'tenant.cross');
    }
}