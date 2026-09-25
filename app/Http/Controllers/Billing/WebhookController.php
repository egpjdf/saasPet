<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Jobs\Billing\ProcessStripeWebhook;
use App\Jobs\Billing\ProcessPaddleWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function stripe(Request $request): JsonResponse
    {
        $signature = $request->header('Stripe-Signature');
        $payload = $request->getContent();

        if (! $signature) {
            return response()->json(['error' => 'Missing Stripe-Signature header'], Response::HTTP_BAD_REQUEST);
        }

        // Verify webhook signature
        if (! $this->verifyStripeSignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        }

        $event = json_decode($payload, true);

        // Dispatch to queue for async processing
        ProcessStripeWebhook::dispatch($event, $signature)
            ->onQueue('billing')
            ->tries(3)
            ->backoff([60, 300, 900]);

        return response()->json(['received' => true]);
    }

    public function paddle(Request $request): JsonResponse
    {
        $signature = $request->header('Paddle-Signature');
        $payload = $request->getContent();

        if (! $signature) {
            return response()->json(['error' => 'Missing Paddle-Signature header'], Response::HTTP_BAD_REQUEST);
        }

        // Verify webhook signature
        if (! $this->verifyPaddleSignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        }

        $event = json_decode($payload, true);

        // Dispatch to queue for async processing
        ProcessPaddleWebhook::dispatch($event, $signature)
            ->onQueue('billing')
            ->tries(3)
            ->backoff([60, 300, 900]);

        return response()->json(['received' => true]);
    }

    private function verifyStripeSignature(string $payload, string $signature): bool
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        if (! $webhookSecret) {
            return false;
        }

        // Parse signature header
        $elements = explode(',', $signature);
        $timestamp = null;
        $signatures = [];

        foreach ($elements as $element) {
            [$key, $value] = explode('=', $element, 2);
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        // Check timestamp (prevent replay attacks - 5 min tolerance)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        // Verify signature
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

        foreach ($signatures as $sig) {
            if (hash_equals($sig, $expectedSignature)) {
                return true;
            }
        }

        return false;
    }

    private function verifyPaddleSignature(string $payload, string $signature): bool
    {
        $webhookSecret = config('services.paddle.webhook_secret');

        if (! $webhookSecret) {
            return false;
        }

        // Paddle uses a different signature format
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($signature, $expectedSignature);
    }
}