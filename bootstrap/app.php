<?php

declare(strict_types=1);

use App\Http\Middleware\AuthorizeControllerActions;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\PreventCrossTenantAccess;
use App\Http\Middleware\SetOrganizationContext;
use App\Http\Middleware\SetWorkspaceContext;
use App\Http\Middleware\VerifyTenantAccess;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'tenant.org' => SetOrganizationContext::class,
            'tenant.ws' => SetWorkspaceContext::class,
            'tenant.access' => VerifyTenantAccess::class,
            'tenant.cross' => PreventCrossTenantAccess::class,
            'authorize' => AuthorizeControllerActions::class,
            '2fa.required' => EnsureTwoFactorEnabled::class,
            'platform.admin' => \App\Http\Middleware\RequirePlatformAdmin::class,
        ]);

        $middleware->group('tenant', [
            'tenant.org',
            'tenant.ws',
            'tenant.access',
            'tenant.cross',
        ]);

        $middleware->group('tenant.auth', [
            'tenant',
            'authorize',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'UNAUTHENTICATED',
            ], 401);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage() ?? 'This action is unauthorized.',
                'error' => 'UNAUTHORIZED',
            ], 403);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'error' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
            ], 422);
        });
    })
    ->withProviders([
        \App\Providers\TenantServiceProvider::class,
        \App\Providers\AuthServiceProvider::class,
    ])
    ->create();