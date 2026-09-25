<?php

declare(strict_types=1);

namespace App\Jobs\LGPD;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeleteFromSentry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $userId,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        try {
            // Request user data deletion in Sentry
            // This would typically use Sentry's API to delete user data
            Http::withHeaders([
                'Authorization' => 'Bearer ' . config('sentry.auth_token'),
            ])->delete("https://sentry.io/api/0/organizations/{config('sentry.organization')}/user-feedback/", [
                'user_id' => $user->id,
            ]);

            Log::info('Sentry data deletion requested for LGPD', [
                'user_id' => $user->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to request Sentry deletion', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}