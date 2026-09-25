<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Billing\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Cashier;

class PortalController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $user = Auth::user();
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        if ($tenantContext->isPlatformAdmin()) {
            return redirect()->route('admin.billing.portal');
        }

        $organization = Organization::find($tenantContext->organizationId());

        if (! $organization) {
            return redirect()->back()->with('error', 'Organization not found');
        }

        // Get the organization's primary subscription
        $subscription = Subscription::where('organization_id', $organization->id)
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->orWhereIn('paddle_status', ['active', 'trialing', 'past_due'])
            ->first();

        if (! $subscription) {
            return redirect()->back()->with('error', 'No active subscription found');
        }

        // Determine provider
        $provider = $subscription->stripe_id ? 'stripe' : ($subscription->paddle_id ? 'paddle' : null);

        if (! $provider) {
            return redirect()->back()->with('error', 'No billing provider configured');
        }

        $url = match ($provider) {
            'stripe' => $this->getStripePortalUrl($organization, $subscription),
            'paddle' => $this->getPaddlePortalUrl($organization, $subscription),
            default => null,
        };

        if (! $url) {
            return redirect()->back()->with('error', 'Could not generate portal URL');
        }

        return redirect($url);
    }

    public function stripe(Organization $organization): JsonResponse
    {
        $subscription = Subscription::where('organization_id', $organization->id)
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->first();

        if (! $subscription || ! $subscription->stripe_id) {
            return response()->json(['error' => 'No Stripe subscription found'], 404);
        }

        $url = $this->getStripePortalUrl($organization, $subscription);

        return response()->json(['url' => $url]);
    }

    public function paddle(Organization $organization): JsonResponse
    {
        $subscription = Subscription::where('organization_id', $organization->id)
            ->whereIn('paddle_status', ['active', 'trialing', 'past_due'])
            ->first();

        if (! $subscription || ! $subscription->paddle_id) {
            return response()->json(['error' => 'No Paddle subscription found'], 404);
        }

        $url = $this->getPaddlePortalUrl($organization, $subscription);

        return response()->json(['url' => $url]);
    }

    private function getStripePortalUrl(Organization $organization, Subscription $subscription): ?string
    {
        try {
            $stripeCustomer = $organization->asStripeCustomer();

            return $stripeCustomer
                ->billingPortalUrl()
                ->withReturnUrl(route('organization.billing', ['organization' => $organization]))
                ->create();
        } catch (\Throwable $e) {
            \Log::error('Failed to create Stripe portal URL', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function getPaddlePortalUrl(Organization $organization, Subscription $subscription): ?string
    {
        try {
            // Paddle uses a different approach - redirect to Paddle's customer portal
            // This would typically use Paddle.js or a direct link
            return config('services.paddle.portal_url') . '?subscription_id=' . $subscription->paddle_id;
        } catch (\Throwable $e) {
            \Log::error('Failed to create Paddle portal URL', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}