<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use Illuminate\Queue\SerializesModels;

trait HasTenantContext
{
    use SerializesModels;

    public function __construct(
        public readonly string $organizationId,
        public readonly string $workspaceId,
        public readonly bool $isPlatformAdmin = false
    ) {}

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
}