<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PushChannel
{
    public function send($notifiable, Notification $notification): void
    {
        // Implement push notification (Firebase, Expo, etc.)
        Log::info('Push notification sent', [
            'to' => $notifiable->id,
            'notification_type' => get_class($notification),
        ]);
    }
}