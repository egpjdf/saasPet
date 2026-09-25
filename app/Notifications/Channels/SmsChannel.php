<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send($notifiable, Notification $notification): void
    {
        // Implement SMS provider (Twilio, Vonage, etc.)
        Log::info('SMS notification sent', [
            'to' => $notifiable->phone ?? 'unknown',
            'notification_type' => get_class($notification),
        ]);
    }
}