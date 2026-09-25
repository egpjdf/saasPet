<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Notifications\Base\BaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResendChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (! $notification instanceof BaseNotification) {
            Log::warning('ResendChannel only supports BaseNotification');
            return;
        }

        $message = $notification->toMail($notifiable);

        if (! $notifiable->email) {
            Log::warning('Notifiable has no email', ['notifiable_id' => $notifiable->id]);
            return;
        }

        $apiKey = config('services.resend.key');

        if (! $apiKey) {
            Log::error('Resend API key not configured');
            return;
        }

        $from = config('services.resend.from', 'noreply@saaspet.com');
        $replyTo = config('services.resend.reply_to', 'support@saaspet.com');

        $payload = [
            'from' => $from,
            'to' => [$notifiable->email],
            'subject' => $message->subject,
            'html' => $message->render(),
            'reply_to' => $replyTo,
            'tags' => [
                ['name' => 'notification_type', 'value' => $notification->type],
                ['name' => 'environment', 'value' => config('app.env')],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://api.resend.com/emails', $payload);

            if (! $response->successful()) {
                Log::error('Resend API error', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'notification_type' => $notification->type,
                ]);

                throw new \Exception('Resend API error: ' . $response->status());
            }

            Log::info('Email sent via Resend', [
                'to' => $notifiable->email,
                'notification_type' => $notification->type,
                'resend_id' => $response->json('id') ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Resend send failed', [
                'error' => $e->getMessage(),
                'notification_type' => $notification->type,
            ]);

            throw $e;
        }
    }
}