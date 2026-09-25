<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        // Platform Admin tem acesso total
        if ($tenantContext->isPlatformAdmin()) {
            $user = Auth::user();
            if ($user && $user->isPlatformAdmin()) {
                return $next($request);
            }
            return response()->json([
                'message' => 'Platform admin access required',
                'error' => 'PLATFORM_ADMIN_REQUIRED',
            ], 403);
        }

        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'UNAUTHENTICATED',
            ], 401);
        }

        $organizationId = $tenantContext->organizationId();
        $workspaceId = $tenantContext->workspaceId();

        // Verifica se user pertence à organization
        if ($organizationId && (string) $user->organization_id !== $organizationId) {
            return response()->json([
                'message' => 'Access denied: user does not belong to this organization',
                'error' => 'ORGANIZATION_ACCESS_DENIED',
            ], 403);
        }

        // Verifica se user pertence ao workspace (se definido)
        if ($workspaceId && (string) $user->workspace_id !== $workspaceId) {
            // Org Admin pode acessar outros workspaces da mesma org
            if (! $user->isOrgAdmin() || (string) $user->organization_id !== $organizationId) {
                return response()->json([
                    'message' => 'Access denied: user does not belong to this workspace',
                    'error' => 'WORKSPACE_ACCESS_DENIED',
                ], 403);
            }
        }

        return $next($request);
    }
}