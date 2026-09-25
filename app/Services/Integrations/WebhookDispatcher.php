<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Integrations\WebhookEndpoint;
use App\Models\Integrations\WebhookDelivery;
use App\Jobs\Integrations\DeliverWebhook;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class WebhookDispatcher
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {}

    public function dispatch(string $event, array $payload): void
    {
        $endpoints = $this->getActiveEndpointsForEvent($event);

        if ($endpoints->isEmpty()) {
            Log::info('No active webhook endpoints for event', ['event' => $event]);
            return;
        }

        foreach ($endpoints as $endpoint) {
            DeliverWebhook::dispatch(
                $endpoint->id,
                $event,
                $payload,
                $this->tenantContext->organizationId(),
                $this->tenantContext->workspaceId(),
                $this->tenantContext->isPlatformAdmin()
            )->onQueue('webhooks');
        }

        Log::info('Webhook events dispatched', [
            'event' => $event,
            'endpoint_count' => $endpoints->count(),
        ]);
    }

    public function dispatchToEndpoint(WebhookEndpoint $endpoint, string $event, array $payload): void
    {
        if (! $endpoint->handlesEvent($event)) {
            return;
        }

        DeliverWebhook::dispatch(
            $endpoint->id,
            $event,
            $payload,
            $this->tenantContext->organizationId(),
            $this->tenantContext->workspaceId(),
            $this->tenantContext->isPlatformAdmin()
        )->onQueue('webhooks');
    }

    public function testEndpoint(WebhookEndpoint $endpoint): array
    {
        $testPayload = [
            'event' => 'webhook.test',
            'timestamp' => now()->toISOString(),
            'data' => [
                'message' => 'Test webhook from Saaspet',
                'endpoint_id' => $endpoint->id,
            ],
        ];

        DeliverWebhook::dispatchSync(
            $endpoint->id,
            'webhook.test',
            $testPayload,
            $this->tenantContext->organizationId(),
            $this->tenantContext->workspaceId(),
            $this->tenantContext->isPlatformAdmin()
        );

        $delivery = $endpoint->deliveries()->latest()->first();

        return [
            'success' => $delivery && $delivery->isDelivered(),
            'status_code' => $delivery?->response_code,
            'response_body' => $delivery?->response_body,
            'error' => $delivery?->error,
        ];
    }

    private function getActiveEndpointsForEvent(string $event): Collection
    {
        $query = WebhookEndpoint::where('active', true)
            ->where(function ($q) use ($event) {
                $q->whereJsonContains('events', $event)
                  ->orWhereJsonContains('events', '*');
            });

        // Apply tenant scopes
        if (! $this->tenantContext->isPlatformAdmin()) {
            $query->where('organization_id', $this->tenantContext->organizationId());

            if ($this->tenantContext->hasWorkspace()) {
                $query->where('workspace_id', $this->tenantContext->workspaceId());
            }
        }

        return $query->get();
    }
}