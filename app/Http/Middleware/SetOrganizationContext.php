<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        // Platform Admin route (/admin) - não resolve organization
        if ($request->is('admin*') || $request->routeIs('admin.*')) {
            $tenantContext->setPlatformAdmin(true);
            return $next($request);
        }

        // Resolve organization pelo slug do path
        $orgSlug = $request->route('organization') ?? $request->route('org');

        if (! $orgSlug) {
            return response()->json([
                'message' => 'Organization context required',
                'error' => 'MISSING_ORGANIZATION_CONTEXT',
            ], 400);
        }

        $organization = \App\Models\Organization::withoutOrganizationScope()
            ->where('slug', $orgSlug)
            ->first();

        if (! $organization) {
            return response()->json([
                'message' => 'Organization not found',
                'error' => 'ORGANIZATION_NOT_FOUND',
            ], 404);
        }

        if (! $organization->isActive()) {
            return response()->json([
                'message' => 'Organization is not active',
                'error' => 'ORGANIZATION_INACTIVE',
            ], 403);
        }

        $tenantContext->setOrganizationId((string) $organization->id);

        // Compartilhar com views/Inertia
        $request->attributes->set('organization', $organization);
        $request->attributes->set('organization_id', $organization->id);

        return $next($request);
    }
}