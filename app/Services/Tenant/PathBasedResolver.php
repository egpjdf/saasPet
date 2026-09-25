<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Organization;
use App\Models\Workspace;
use Illuminate\Http\Request;

class PathBasedResolver
{
    public function resolve(Request $request): ?ResolvedTenant
    {
        $orgSlug = $request->route('organization') ?? $request->route('org');

        if (! $orgSlug) {
            return null;
        }

        $organization = Organization::withoutOrganizationScope()
            ->where('slug', $orgSlug)
            ->first();

        if (! $organization) {
            return null;
        }

        // Se não há workspace na rota, retorna apenas organization
        $wsSlug = $request->route('workspace') ?? $request->route('ws');

        if (! $wsSlug) {
            return new ResolvedTenant(
                organization: $organization,
                workspace: null,
            );
        }

        $workspace = Workspace::withoutWorkspaceScope()
            ->where('organization_id', $organization->id)
            ->where('slug', $wsSlug)
            ->first();

        if (! $workspace) {
            return null;
        }

        return new ResolvedTenant(
            organization: $organization,
            workspace: $workspace,
        );
    }
}