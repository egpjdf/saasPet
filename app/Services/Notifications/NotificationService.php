<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\Notifications\Notification;
use App\Models\Notifications\NotificationPreference;
use App\Notifications\Base\BaseNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationService
{
    public function send(User $user, BaseNotification $notification): void
    {
        if (! $notification->shouldSend($user)) {
            return;
        }

        $notification->dispatch($user);
    }

    public function sendToMany(Collection $users, BaseNotification $notification): void
    {
        foreach ($users as $user) {
            $this->send($user, $notification);
        }
    }

    public function sendBatch(Collection $users, BaseNotification $notification, int $batchSize = 100): void
    {
        $users->chunk($batchSize)->each(function ($chunk) use ($notification) {
            $this->sendToMany($chunk, $notification);
        });
    }

    public function getUserNotifications(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $user->id)
            ->whereNull('archived_at')
            ->latest();

        if (! empty($filters['unread_only'])) {
            $query->whereNull('read_at');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $notificationId)
            ->first();

        if (! $notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function archive(User $user, string $notificationId): bool
    {
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $notificationId)
            ->first();

        if (! $notification) {
            return false;
        }

        $notification->markAsArchived();
        return true;
    }

    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->whereNull('archived_at')
            ->count();
    }

    public function getPreferences(User $user): Collection
    {
        return NotificationPreference::where('user_id', $user->id)
            ->get()
            ->groupBy('type');
    }

    public function updatePreference(User $user, string $type, string $channel, bool $enabled, string $frequency = 'immediate'): NotificationPreference
    {
        return NotificationPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'workspace_id' => $user->workspace_id,
                'type' => $type,
                'channel' => $channel,
            ],
            [
                'enabled' => $enabled,
                'frequency' => $frequency,
            ]
        );
    }

    public function syncDefaultPreferences(User $user): void
    {
        $defaults = NotificationPreference::getDefaultPreferences();

        foreach ($defaults as $default) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'organization_id' => $user->organization_id,
                    'workspace_id' => $user->workspace_id,
                    'type' => $default['type'],
                    'channel' => $default['channel'],
                ],
                [
                    'enabled' => $default['enabled'],
                    'frequency' => $default['frequency'],
                ]
            );
        }
    }

    public function getDigest(User $user, string $frequency = 'daily'): Collection
    {
        $since = match ($frequency) {
            'daily' => now()->subDay(),
            'weekly' => now()->subWeek(),
            default => now()->subDay(),
        };

        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->whereNull('archived_at')
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy('type');
    }
}