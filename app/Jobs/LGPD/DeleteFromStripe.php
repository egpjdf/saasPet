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

class DeleteFromStripe implements ShouldQueue
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
            ->whereNotNull('stripe_id')
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                // Cancel subscription in Stripe
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.stripe.secret'),
                    'Stripe-Version' => '2023-10-16',
                ])->delete("https://api.stripe.com/v1/subscriptions/{$subscription->stripe_id}");

                // Delete customer if no other subscriptions
                $customerId = $subscription->organization->stripe_id ?? null;
                if ($customerId) {
                    $otherSubs = Subscription::where('organization_id', $subscription->organization_id)
                        ->where('stripe_id', '!=', $subscription->stripe_id)
                        ->whereNotNull('stripe_id')
                        ->exists();

                    if (! $otherSubs) {
                        Http::withHeaders([
                            'Authorization' => 'Bearer ' . config('services.stripe.secret'),
                        ])->delete("https://api.stripe.com/v1/customers/{$customerId}");
                    }
                }

                Log::info('Stripe data deleted for LGPD', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to delete from Stripe', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}