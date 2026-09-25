<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Models\Billing\Invoice;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaddleWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasTenantContext;

    public function __construct(
        public array $event,
        public string $signature,
    ) {}

    public function handle(): void
    {
        $eventType = $this->event['event_type'] ?? '';

        Log::info('Processing Paddle webhook', [
            'event_type' => $eventType,
            'event_id' => $this->event['event_id'] ?? null,
        ]);

        try {
            match ($eventType) {
                'subscription_created' => $this->handleSubscriptionCreated(),
                'subscription_updated' => $this->handleSubscriptionUpdated(),
                'subscription_cancelled' => $this->handleSubscriptionCancelled(),
                'transaction_completed' => $this->handleTransactionCompleted(),
                'transaction_refunded' => $this->handleTransactionRefunded(),
                default => Log::info('Unhandled Paddle event', ['type' => $eventType]),
            };
        } catch (\Throwable $e) {
            Log::error('Paddle webhook processing failed', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function handleSubscriptionCreated(): void
    {
        $data = $this->event['data'] ?? [];
        $organizationId = $data['custom_data']['organization_id'] ?? null;
        $workspaceId = $data['custom_data']['workspace_id'] ?? null;
        $userId = $data['custom_data']['user_id'] ?? null;

        if (! $organizationId) {
            Log::warning('Subscription created without organization_id', ['subscription_id' => $data['id']]);
            return;
        }

        $subscription = Subscription::updateOrCreate(
            ['paddle_id' => $data['id']],
            [
                'organization_id' => $organizationId,
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
                'name' => 'default',
                'paddle_status' => 'active',
                'paddle_price_id' => $data['price_id'] ?? null,
                'quantity' => $data['quantity'] ?? 1,
                'trial_ends_at' => isset($data['trial_ends_at']) ? \Carbon\Carbon::parse($data['trial_ends_at']) : null,
            ]
        );

        Log::info('Paddle subscription created', ['subscription_id' => $subscription->id]);
    }

    private function handleSubscriptionUpdated(): void
    {
        $data = $this->event['data'] ?? [];

        $subscription = Subscription::where('paddle_id', $data['id'])->first();

        if (! $subscription) {
            Log::warning('Paddle subscription not found for update', ['paddle_id' => $data['id']]);
            return;
        }

        $subscription->update([
            'paddle_status' => $data['status'] ?? $subscription->paddle_status,
            'paddle_price_id' => $data['price_id'] ?? $subscription->paddle_price_id,
            'quantity' => $data['quantity'] ?? $subscription->quantity,
            'trial_ends_at' => isset($data['trial_ends_at']) ? \Carbon\Carbon::parse($data['trial_ends_at']) : $subscription->trial_ends_at,
            'cancels_at' => isset($data['next_billed_at']) && $data['status'] === 'cancelled' ? \Carbon\Carbon::parse($data['next_billed_at']) : null,
        ]);

        Log::info('Paddle subscription updated', ['subscription_id' => $subscription->id]);
    }

    private function handleSubscriptionCancelled(): void
    {
        $data = $this->event['data'] ?? [];

        $subscription = Subscription::where('paddle_id', $data['id'])->first();

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'paddle_status' => 'cancelled',
            'cancelled_at' => now(),
            'ends_at' => isset($data['effective_at']) ? \Carbon\Carbon::parse($data['effective_at']) : now(),
        ]);

        Log::info('Paddle subscription canceled', ['subscription_id' => $subscription->id]);
    }

    private function handleTransactionCompleted(): void
    {
        $data = $this->event['data'] ?? [];
        $subscriptionId = $data['subscription_id'] ?? null;

        if (! $subscriptionId) {
            return;
        }

        $subscription = Subscription::where('paddle_id', $subscriptionId)->first();

        if (! $subscription) {
            Log::warning('Paddle subscription not found for transaction', ['subscription_id' => $subscriptionId]);
            return;
        }

        // Create invoice record
        Invoice::updateOrCreate(
            ['paddle_id' => $data['id']],
            [
                'organization_id' => $subscription->organization_id,
                'workspace_id' => $subscription->workspace_id,
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'number' => $data['details']['invoice_number'] ?? $data['id'],
                'status' => 'paid',
                'currency' => $data['currency'],
                'subtotal_cents' => $data['details']['subtotal'] ?? 0,
                'tax_cents' => $data['details']['tax'] ?? 0,
                'total_cents' => $data['details']['total'] ?? 0,
                'amount_paid_cents' => $data['details']['total'] ?? 0,
                'amount_due_cents' => 0,
                'billing_reason' => 'subscription_cycle',
                'period_start' => isset($data['details']['period_from']) ? \Carbon\Carbon::parse($data['details']['period_from']) : null,
                'period_end' => isset($data['details']['period_to']) ? \Carbon\Carbon::parse($data['details']['period_to']) : null,
                'due_date' => now(),
                'paid_at' => now(),
                'hosted_invoice_url' => $data['details']['invoice_url'] ?? null,
            ]
        );

        Log::info('Paddle invoice recorded', ['invoice_id' => $data['id']]);
    }

    private function handleTransactionRefunded(): void
    {
        $data = $this->event['data'] ?? [];
        $subscriptionId = $data['subscription_id'] ?? null;

        if (! $subscriptionId) {
            return;
        }

        $subscription = Subscription::where('paddle_id', $subscriptionId)->first();

        if (! $subscription || ! $subscription->user) {
            return;
        }

        // Notify user about refund
        $subscription->user->notify(new \App\Notifications\Billing\InvoiceRefunded($subscription, $data));

        Log::info('Paddle refund notification sent', ['subscription_id' => $subscription->id]);
    }
}