<?php

declare(strict_types=1);

namespace App\Services\Tenant;

class TenantContext
{
    private ?string $organizationId = null;
    private ?string $workspaceId = null;
    private bool $isPlatformAdmin = false;

    public function setOrganizationId(?string $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function setWorkspaceId(?string $workspaceId): void
    {
        $this->workspaceId = $workspaceId;
    }

    public function setPlatformAdmin(bool $isPlatformAdmin): void
    {
        $this->isPlatformAdmin = $isPlatformAdmin;
    }

    public function organizationId(): ?string
    {
        return $this->organizationId;
    }

    public function workspaceId(): ?string
    {
        return $this->workspaceId;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->isPlatformAdmin;
    }

    public function hasOrganization(): bool
    {
        return $this->organizationId !== null;
    }

    public function hasWorkspace(): bool
    {
        return $this->workspaceId !== null;
    }

    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'is_platform_admin' => $this->isPlatformAdmin,
        ];
    }

    public function clear(): void
    {
        $this->organizationId = null;
        $this->workspaceId = null;
        $this->isPlatformAdmin = false;
    }
}