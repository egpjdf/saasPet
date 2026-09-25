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
use Laravel\Cashier\Cashier;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasTenantContext;

    public function __construct(
        public array $event,
        public string $signature,
    ) {}

    public function handle(): void
    {
        $eventType = $this->event['type'] ?? '';

        Log::info('Processing Stripe webhook', [
            'event_type' => $eventType,
            'event_id' => $this->event['id'] ?? null,
        ]);

        try {
            match ($eventType) {
                'checkout.session.completed' => $this->handleCheckoutCompleted(),
                'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded(),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed(),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated(),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted(),
                'customer.subscription.trial_will_end' => $this->handleTrialWillEnd(),
                default => Log::info('Unhandled Stripe event', ['type' => $eventType]),
            };
        } catch (\Throwable $e) {
            Log::error('Stripe webhook processing failed', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function handleCheckoutCompleted(): void
    {
        $session = $this->event['data']['object'];
        $organizationId = $session['metadata']['organization_id'] ?? null;
        $workspaceId = $session['metadata']['workspace_id'] ?? null;
        $userId = $session['metadata']['user_id'] ?? null;

        if (! $organizationId) {
            Log::warning('Checkout completed without organization_id', ['session_id' => $session['id']]);
            return;
        }

        // Create or update subscription
        $subscription = Subscription::updateOrCreate(
            ['stripe_id' => $session['subscription']],
            [
                'organization_id' => $organizationId,
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
                'name' => 'default',
                'stripe_status' => 'active',
                'stripe_price_id' => $session['line_items']['data'][0]['price']['id'] ?? null,
                'quantity' => $session['line_items']['data'][0]['quantity'] ?? 1,
            ]
        );

        Log::info('Subscription created from checkout', ['subscription_id' => $subscription->id]);
    }

    private function handleInvoicePaymentSucceeded(): void
    {
        $invoice = $this->event['data']['object'];
        $stripeSubscriptionId = $invoice['subscription'] ?? null;

        if (! $stripeSubscriptionId) {
            return;
        }

        $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();

        if (! $subscription) {
            Log::warning('Subscription not found for invoice', ['stripe_subscription_id' => $stripeSubscriptionId]);
            return;
        }

        // Create invoice record
        Invoice::updateOrCreate(
            ['stripe_id' => $invoice['id']],
            [
                'organization_id' => $subscription->organization_id,
                'workspace_id' => $subscription->workspace_id,
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'number' => $invoice['number'],
                'status' => 'paid',
                'currency' => $invoice['currency'],
                'subtotal_cents' => $invoice['subtotal'],
                'tax_cents' => $invoice['tax'] ?? 0,
                'total_cents' => $invoice['amount_paid'],
                'amount_paid_cents' => $invoice['amount_paid'],
                'amount_due_cents' => $invoice['amount_due'],
                'billing_reason' => $invoice['billing_reason'],
                'period_start' => $invoice['period_start'] ? \Carbon\Carbon::createFromTimestamp($invoice['period_start']) : null,
                'period_end' => $invoice['period_end'] ? \Carbon\Carbon::createFromTimestamp($invoice['period_end']) : null,
                'due_date' => $invoice['due_date'] ? \Carbon\Carbon::createFromTimestamp($invoice['due_date']) : null,
                'paid_at' => \Carbon\Carbon::createFromTimestamp($invoice['status_transitions']['paid_at'] ?? time()),
                'hosted_invoice_url' => $invoice['hosted_invoice_url'] ?? null,
                'invoice_pdf' => $invoice['invoice_pdf'] ?? null,
            ]
        );

        Log::info('Invoice recorded', ['invoice_id' => $invoice['id']]);
    }

    private function handleInvoicePaymentFailed(): void
    {
        $invoice = $this->event['data']['object'];
        $stripeSubscriptionId = $invoice['subscription'] ?? null;

        if (! $stripeSubscriptionId) {
            return;
        }

        $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();

        if (! $subscription) {
            return;
        }

        // Update invoice status
        Invoice::where('stripe_id', $invoice['id'])->update([
            'status' => 'payment_failed',
            'amount_due_cents' => $invoice['amount_due'],
        ]);

        // Notify user
        if ($subscription->user) {
            $subscription->user->notify(new \App\Notifications\Billing\InvoicePaymentFailed($subscription));
        }

        Log::warning('Invoice payment failed', ['invoice_id' => $invoice['id']]);
    }

    private function handleSubscriptionUpdated(): void
    {
        $stripeSubscription = $this->event['data']['object'];

        $subscription = Subscription::where('stripe_id', $stripeSubscription['id'])->first();

        if (! $subscription) {
            Log::warning('Subscription not found for update', ['stripe_subscription_id' => $stripeSubscription['id']]);
            return;
        }

        $subscription->update([
            'stripe_status' => $stripeSubscription['status'],
            'stripe_price_id' => $stripeSubscription['items']['data'][0]['price']['id'] ?? null,
            'quantity' => $stripeSubscription['items']['data'][0]['quantity'] ?? 1,
            'trial_ends_at' => $stripeSubscription['trial_end'] ? \Carbon\Carbon::createFromTimestamp($stripeSubscription['trial_end']) : null,
            'cancels_at' => $stripeSubscription['cancel_at'] ? \Carbon\Carbon::createFromTimestamp($stripeSubscription['cancel_at']) : null,
            'cancelled_at' => $stripeSubscription['canceled_at'] ? \Carbon\Carbon::createFromTimestamp($stripeSubscription['canceled_at']) : null,
            'metadata' => $stripeSubscription['metadata'] ?? [],
        ]);

        Log::info('Subscription updated', ['subscription_id' => $subscription->id]);
    }

    private function handleSubscriptionDeleted(): void
    {
        $stripeSubscription = $this->event['data']['object'];

        $subscription = Subscription::where('stripe_id', $stripeSubscription['id'])->first();

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'stripe_status' => 'canceled',
            'cancelled_at' => now(),
            'ends_at' => now(),
        ]);

        Log::info('Subscription canceled', ['subscription_id' => $subscription->id]);
    }

    private function handleTrialWillEnd(): void
    {
        $stripeSubscription = $this->event['data']['object'];

        $subscription = Subscription::where('stripe_id', $stripeSubscription['id'])->first();

        if (! $subscription || ! $subscription->user) {
            return;
        }

        // Notify user about trial ending
        $subscription->user->notify(new \App\Notifications\Billing\TrialWillEnd($subscription));

        Log::info('Trial ending notification sent', ['subscription_id' => $subscription->id]);
    }
}