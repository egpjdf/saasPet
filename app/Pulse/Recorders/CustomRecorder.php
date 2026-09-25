<?php

declare(strict_types=1);

namespace App\Pulse\Recorders;

use App\Services\Tenant\TenantContext;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Recorders\Recorder;

class CustomRecorder extends Recorder
{
    public function record(): void
    {
        $tenantContext = app(TenantContext::class);

        if (! $tenantContext->hasOrganization() && ! $tenantContext->isPlatformAdmin()) {
            return;
        }

        // Record active organizations
        if ($tenantContext->hasOrganization()) {
            Pulse::gauge('tenant_active_organizations', 1, [
                'organization_id' => $tenantContext->organizationId(),
            ]);
        }

        // Record active workspaces
        if ($tenantContext->hasWorkspace()) {
            Pulse::gauge('tenant_active_workspaces', 1, [
                'organization_id' => $tenantContext->organizationId(),
                'workspace_id' => $tenantContext->workspaceId(),
            ]);
        }

        // Record platform admin activity
        if ($tenantContext->isPlatformAdmin()) {
            Pulse::gauge('platform_admin_active', 1);
        }
    }

    public function name(): string
    {
        return 'custom';
    }

    public function frequency(): int
    {
        return 60; // Record every minute
    }
}