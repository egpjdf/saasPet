<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Organization;
use App\Models\Workspace;

readonly class ResolvedTenant
{
    public function __construct(
        public ?Organization $organization = null,
        public ?Workspace $workspace = null,
        public bool $isPlatformAdmin = false,
    ) {}

    public function hasOrganization(): bool
    {
        return $this->organization !== null;
    }

    public function hasWorkspace(): bool
    {
        return $this->workspace !== null;
    }

    public function organizationId(): ?string
    {
        return $this->organization?->id;
    }

    public function workspaceId(): ?string
    {
        return $this->workspace?->id;
    }

    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId(),
            'workspace_id' => $this->workspaceId(),
            'is_platform_admin' => $this->isPlatformAdmin,
        ];
    }
}