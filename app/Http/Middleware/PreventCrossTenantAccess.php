<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventCrossTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        // Platform Admin bypass
        if ($tenantContext->isPlatformAdmin()) {
            return $next($request);
        }

        $organizationId = $tenantContext->organizationId();
        $workspaceId = $tenantContext->workspaceId();

        // Bloqueia tentativas de manipular tenant_id via query params ou body
        $forbiddenParams = ['organization_id', 'workspace_id', 'tenant_id'];

        foreach ($forbiddenParams as $param) {
            if ($request->has($param)) {
                $requestValue = $request->input($param);
                $contextValue = $param === 'organization_id' ? $organizationId : $workspaceId;

                if ($contextValue && $requestValue !== $contextValue) {
                    // Log de tentativa de cross-tenant access
                    \Log::warning('Cross-tenant access attempt blocked', [
                        'user_id' => $request->user()?->id,
                        'ip' => $request->ip(),
                        'param' => $param,
                        'request_value' => $requestValue,
                        'context_value' => $contextValue,
                        'url' => $request->fullUrl(),
                    ]);

                    return response()->json([
                        'message' => 'Cross-tenant access denied',
                        'error' => 'CROSS_TENANT_ACCESS_DENIED',
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}