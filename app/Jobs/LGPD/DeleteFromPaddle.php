<?php

declare(strict_types=1);

namespace App\Jobs\LGPD;

use App\Models\Billing\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeleteFromPaddle implements ShouldQueue
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

        $subscriptions = Subscription::where('user_id', $user->id)
            ->whereNotNull('paddle_id')
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                // Cancel subscription in Paddle
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.paddle.api_key'),
                    'Content-Type' => 'application/json',
                ])->post("https://api.paddle.com/subscriptions/{$subscription->paddle_id}/cancel", [
                    'effective_from' => 'immediately',
                ]);

                Log::info('Paddle data deleted for LGPD', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to delete from Paddle', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}