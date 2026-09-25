<?php

declare(strict_types=1);

namespace App\Jobs\Organization;

use App\Jobs\Concerns\HasTenantContext;
use App\Models\WorkspaceUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[Tries(3)]
#[Backoff([10, 60, 300])]
#[Timeout(60)]
class SendWorkspaceInviteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasTenantContext;

    public function __construct(
        public readonly string $organizationId,
        public readonly string $workspaceId,
        public readonly string $workspaceUserId,
        public readonly bool $isPlatformAdmin = false
    ) {}

    public function handle(): void
    {
        // Restore tenant context for the job
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);
        $tenantContext->setOrganizationId($this->organizationId);
        $tenantContext->setWorkspaceId($this->workspaceId);
        $tenantContext->setPlatformAdmin($this->isPlatformAdmin);

        $workspaceUser = WorkspaceUser::with(['workspace', 'user', 'inviter'])->find($this->workspaceUserId);

        if (! $workspaceUser || ! $workspaceUser->isPending()) {
            return;
        }

        $workspaceUser->user->notify(new \App\Notifications\WorkspaceInviteNotification($workspaceUser));
    }
}