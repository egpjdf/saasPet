<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

trait HasTenantContext
{
    use SerializesModels;

    protected string $organizationId;
    protected string $workspaceId;
    protected bool $isPlatformAdmin = false;

    public function __construct(
        string $organizationId,
        string $workspaceId,
        bool $isPlatformAdmin = false
    ) {
        $this->organizationId = $organizationId;
        $this->workspaceId = $workspaceId;
        $this->isPlatformAdmin = $isPlatformAdmin;
    }

    public function getOrganizationId(): string
    {
        return $this->organizationId;
    }

    public function getWorkspaceId(): string
    {
        return $this->workspaceId;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->isPlatformAdmin;
    }

    public function serialize(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'is_platform_admin' => $this->isPlatformAdmin,
        ];
    }

    public static function unserialize(array $data): self
    {
        return new self(
            $data['organization_id'],
            $data['workspace_id'],
            $data['is_platform_admin'] ?? false
        );
    }
}