<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetWorkspaceContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        // Platform Admin não precisa de workspace
        if ($tenantContext->isPlatformAdmin()) {
            return $next($request);
        }

        // Rotas de Organization Admin (/{org}/) podem não ter workspace
        if ($request->routeIs('organization.*') && ! $request->route('workspace')) {
            return $next($request);
        }

        // Resolve workspace pelo slug do path
        $wsSlug = $request->route('workspace') ?? $request->route('ws');

        if (! $wsSlug) {
            return response()->json([
                'message' => 'Workspace context required',
                'error' => 'MISSING_WORKSPACE_CONTEXT',
            ], 400);
        }

        $organizationId = $tenantContext->organizationId();

        if (! $organizationId) {
            return response()->json([
                'message' => 'Organization context not set',
                'error' => 'MISSING_ORGANIZATION_CONTEXT',
            ], 400);
        }

        $workspace = \App\Models\Workspace::withoutWorkspaceScope()
            ->where('organization_id', $organizationId)
            ->where('slug', $wsSlug)
            ->first();

        if (! $workspace) {
            return response()->json([
                'message' => 'Workspace not found',
                'error' => 'WORKSPACE_NOT_FOUND',
            ], 404);
        }

        if (! $workspace->isActive()) {
            return response()->json([
                'message' => 'Workspace is not active',
                'error' => 'WORKSPACE_INACTIVE',
            ], 403);
        }

        $tenantContext->setWorkspaceId((string) $workspace->id);

        $request->attributes->set('workspace', $workspace);
        $request->attributes->set('workspace_id', $workspace->id);

        return $next($request);
    }
}