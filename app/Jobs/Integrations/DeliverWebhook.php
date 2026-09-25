<?php

declare(strict_types=1);

namespace App\Jobs\Integrations;

use App\Models\Integrations\WebhookDelivery;
use App\Models\Integrations\WebhookEndpoint;
use App\Services\Integrations\WebhookCircuitBreaker;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasTenantContext;

    public $timeout = 30;
    public $tries = 7;
    public $backoff = [60, 300, 900, 3600, 21600, 86400, 86400]; // 1m, 5m, 15m, 1h, 6h, 24h, 24h

    public function __construct(
        public string $endpointId,
        public string $event,
        public array $payload,
        public string $organizationId = '',
        public string $workspaceId = '',
        public bool $isPlatformAdmin = false,
    ) {}

    public function handle(): void
    {
        $endpoint = WebhookEndpoint::find($this->endpointId);

        if (! $endpoint) {
            Log::warning('DeliverWebhook: Endpoint not found', ['endpoint_id' => $this->endpointId]);
            return;
        }

        // Check circuit breaker
        $circuitBreaker = new WebhookCircuitBreaker($endpoint->id);

        if (! $circuitBreaker->isAvailable()) {
            Log::warning('DeliverWebhook: Circuit breaker open', ['endpoint_id' => $endpoint->id]);
            $this->release(300); // Retry in 5 minutes
            return;
        }

        // Restore tenant context
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);
        $tenantContext->setOrganizationId($this->organizationId);
        $tenantContext->setWorkspaceId($this->workspaceId);
        $tenantContext->setPlatformAdmin($this->isPlatformAdmin);

        $attempt = $this->attempts();
        $delivery = $this->createDeliveryRecord($endpoint, $attempt);

        try {
            $response = $this->deliver($endpoint, $this->payload);

            $this->handleSuccess($delivery, $response, $circuitBreaker);
        } catch (\Throwable $e) {
            $this->handleFailure($delivery, $e, $circuitBreaker);
            throw $e;
        }
    }

    private function createDeliveryRecord(WebhookEndpoint $endpoint, int $attempt): WebhookDelivery
    {
        return WebhookDelivery::create([
            'organization_id' => $this->organizationId ?: $endpoint->organization_id,
            'workspace_id' => $this->workspaceId ?: $endpoint->workspace_id,
            'endpoint_id' => $endpoint->id,
            'event' => $this->event,
            'payload' => $this->payload,
            'attempt' => $attempt,
        ]);
    }

    private function deliver(WebhookEndpoint $endpoint, array $payload): \Illuminate\Http\Client\Response
    {
        $signature = $this->generateSignature($payload, $endpoint->secret);

        return Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $this->event,
                'X-Webhook-Delivery' => $endpoint->id,
                'User-Agent' => 'Saaspet-Webhook/1.0',
            ])
            ->post($endpoint->url, $payload);
    }

    private function generateSignature(array $payload, string $secret): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return 'sha256=' . hash_hmac('sha256', $json, $secret);
    }

    private function handleSuccess(WebhookDelivery $delivery, \Illuminate\Http\Client\Response $response, WebhookCircuitBreaker $circuitBreaker): void
    {
        $delivery->update([
            'response_code' => $response->status(),
            'response_body' => $response->body(),
            'delivered_at' => now(),
        ]);

        $circuitBreaker->recordSuccess();

        // Reset endpoint retry count on success
        $endpoint = WebhookEndpoint::find($this->endpointId);
        if ($endpoint) {
            $endpoint->update([
                'retry_count' => 0,
                'last_delivery_at' => now(),
                'last_status_code' => $response->status(),
            ]);
        }

        Log::info('Webhook delivered', [
            'endpoint_id' => $this->endpointId,
            'event' => $this->event,
            'status_code' => $response->status(),
            'attempt' => $this->attempts(),
        ]);
    }

    private function handleFailure(WebhookDelivery $delivery, \Throwable $e, WebhookCircuitBreaker $circuitBreaker): void
    {
        $delivery->update([
            'response_code' => $e instanceof \Illuminate\Http\Client\ConnectionException ? 0 : 500,
            'response_body' => $e->getMessage(),
            'failed_at' => now(),
            'error' => $e->getMessage(),
        ]);

        $circuitBreaker->recordFailure();

        // Increment endpoint retry count
        $endpoint = WebhookEndpoint::find($this->endpointId);
        if ($endpoint) {
            $endpoint->increment('retry_count');
        }

        Log::error('Webhook delivery failed', [
            'endpoint_id' => $this->endpointId,
            'event' => $this->event,
            'attempt' => $this->attempts(),
            'error' => $e->getMessage(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        // After all retries exhausted, mark as permanently failed
        $delivery = WebhookDelivery::where('endpoint_id', $this->endpointId)
            ->where('event', $this->event)
            ->where('attempt', $this->attempts())
            ->first();

        if ($delivery) {
            $delivery->update([
                'failed_at' => now(),
                'error' => 'Max retries exhausted: ' . $e->getMessage(),
            ]);
        }

        // Move to DLQ table or log for manual review
        Log::critical('Webhook permanently failed - moved to DLQ', [
            'endpoint_id' => $this->endpointId,
            'event' => $this->event,
            'payload' => $this->payload,
            'error' => $e->getMessage(),
        ]);
    }
}