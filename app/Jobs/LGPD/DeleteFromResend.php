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

class DeleteFromResend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $userId,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user || ! $user->email) {
            return;
        }

        try {
            // Suppress email in Resend (add to suppression list)
            Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.resend.key'),
            ])->post('https://api.resend.com/suppressions', [
                'email' => $user->email,
                'reason' => 'unsubscribe',
            ]);

            Log::info('Resend suppression added for LGPD', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to suppress in Resend', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}