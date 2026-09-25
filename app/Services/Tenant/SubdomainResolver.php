<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Organization;
use App\Models\Workspace;
use Illuminate\Http\Request;

class SubdomainResolver
{
    public function resolve(Request $request): ?ResolvedTenant
    {
        $host = $request->getHost();
        $domainParts = explode('.', $host);

        // Precisa de pelo menos 3 partes: {org}.saaspet.com ou {org}.{ws}.saaspet.com
        if (count($domainParts) < 3) {
            return null;
        }

        $orgSlug = $domainParts[0];

        $organization = Organization::withoutOrganizationScope()
            ->where('slug', $orgSlug)
            ->first();

        if (! $organization) {
            return null;
        }

        // Se tem 4 partes: {org}.{ws}.saaspet.com
        if (count($domainParts) >= 4) {
            $wsSlug = $domainParts[1];

            $workspace = Workspace::withoutWorkspaceScope()
                ->where('organization_id', $organization->id)
                ->where('slug', $wsSlug)
                ->first();

            if ($workspace) {
                return new ResolvedTenant(
                    organization: $organization,
                    workspace: $workspace,
                );
            }
        }

        return new ResolvedTenant(
            organization: $organization,
            workspace: null,
        );
    }
}