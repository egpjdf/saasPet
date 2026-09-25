<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Response;

class SetSentryContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);
        $user = Auth::user();

        Sentry\configureScope(function (Scope $scope) use ($tenantContext, $user) {
            // Set user context
            if ($user) {
                $scope->setUser([
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->name,
                ]);
            }

            // Set tenant context
            if ($tenantContext->hasOrganization()) {
                $scope->setTag('organization_id', $tenantContext->organizationId());
            }

            if ($tenantContext->hasWorkspace()) {
                $scope->setTag('workspace_id', $tenantContext->workspaceId());
            }

            if ($tenantContext->isPlatformAdmin()) {
                $scope->setTag('is_platform_admin', 'true');
            }

            // Set extra context
            $scope->setExtras([
                'tenant_context' => $tenantContext->toArray(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);
        });

        return $next($request);
    }
}