<?php

declare(strict_types=1);

namespace App\Jobs\Notifications;

use App\Models\User;
use App\Notifications\Base\BaseNotification;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasTenantContext;

    public function __construct(
        public string $userId,
        public string $notificationClass,
        public array $notificationData = [],
        public string $organizationId = '',
        public string $workspaceId = '',
        public bool $isPlatformAdmin = false,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('SendNotification: User not found', ['user_id' => $this->userId]);
            return;
        }

        $notificationClass = $this->notificationClass;

        if (! class_exists($notificationClass)) {
            Log::error('SendNotification: Notification class not found', ['class' => $notificationClass]);
            return;
        }

        if (! is_subclass_of($notificationClass, BaseNotification::class)) {
            Log::error('SendNotification: Invalid notification class', ['class' => $notificationClass]);
            return;
        }

        // Restore tenant context
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);
        $tenantContext->setOrganizationId($this->organizationId);
        $tenantContext->setWorkspaceId($this->workspaceId);
        $tenantContext->setPlatformAdmin($this->isPlatformAdmin);

        try {
            $notification = new $notificationClass($this->notificationData);
            $notification->dispatch($user);

            Log::info('Notification sent', [
                'user_id' => $user->id,
                'notification' => $notificationClass,
                'organization_id' => $this->organizationId,
                'workspace_id' => $this->workspaceId,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendNotification failed', [
                'user_id' => $user->id,
                'notification' => $notificationClass,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }

    public function backoff(): array
    {
        return [60, 300, 900, 3600]; // 1m, 5m, 15m, 1h
    }

    public function maxAttempts(): int
    {
        return 5;
    }
}