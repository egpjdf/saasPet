<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhookCircuitBreaker
{
    private const FAILURE_THRESHOLD = 5;
    private const TIMEOUT_SECONDS = 60;
    private const HALF_OPEN_REQUESTS = 3;

    public function __construct(
        private string $endpointId,
    ) {}

    public function isAvailable(): bool
    {
        $state = $this->getState();

        if ($state === 'closed') {
            return true;
        }

        if ($state === 'open') {
            // Check if timeout has passed to move to half-open
            $lastFailure = Cache::get("webhook_circuit_breaker:{$this->endpointId}:last_failure");

            if ($lastFailure && (time() - $lastFailure) >= self::TIMEOUT_SECONDS) {
                $this->transitionToHalfOpen();
                return true;
            }

            return false;
        }

        // Half-open state - allow limited requests
        $halfOpenCount = Cache::get("webhook_circuit_breaker:{$this->endpointId}:half_open_count", 0);

        return $halfOpenCount < self::HALF_OPEN_REQUESTS;
    }

    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === 'half_open') {
            $successCount = Cache::increment("webhook_circuit_breaker:{$this->endpointId}:half_open_success");

            if ($successCount >= self::HALF_OPEN_REQUESTS) {
                $this->transitionToClosed();
            }
        } elseif ($state === 'closed') {
            // Reset failure count on success
            Cache::forget("webhook_circuit_breaker:{$this->endpointId}:failure_count");
        }
    }

    public function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === 'half_open') {
            // Any failure in half-open goes back to open
            $this->transitionToOpen();
            return;
        }

        $failureCount = Cache::increment("webhook_circuit_breaker:{$this->endpointId}:failure_count");

        if ($failureCount >= self::FAILURE_THRESHOLD) {
            $this->transitionToOpen();
        }

        Cache::put("webhook_circuit_breaker:{$this->endpointId}:last_failure", time(), 3600);
    }

    public function getState(): string
    {
        if (Cache::get("webhook_circuit_breaker:{$this->endpointId}:state") === 'open') {
            return 'open';
        }

        if (Cache::get("webhook_circuit_breaker:{$this->endpointId}:state") === 'half_open') {
            return 'half_open';
        }

        return 'closed';
    }

    private function transitionToOpen(): void
    {
        Cache::put("webhook_circuit_breaker:{$this->endpointId}:state", 'open', 3600);
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:half_open_count");
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:half_open_success");

        Log::warning('Webhook circuit breaker opened', ['endpoint_id' => $this->endpointId]);
    }

    private function transitionToHalfOpen(): void
    {
        Cache::put("webhook_circuit_breaker:{$this->endpointId}:state", 'half_open', 3600);
        Cache::put("webhook_circuit_breaker:{$this->endpointId}:half_open_count", 0, 3600);
        Cache::put("webhook_circuit_breaker:{$this->endpointId}:half_open_success", 0, 3600);

        Log::info('Webhook circuit breaker half-open', ['endpoint_id' => $this->endpointId]);
    }

    private function transitionToClosed(): void
    {
        Cache::put("webhook_circuit_breaker:{$this->endpointId}:state", 'closed', 3600);
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:failure_count");
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:half_open_count");
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:half_open_success");
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:last_failure");

        Log::info('Webhook circuit breaker closed', ['endpoint_id' => $this->endpointId]);
    }

    public function reset(): void
    {
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:state");
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:failure_count");
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:half_open_count");
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:half_open_success");
        Cache::forget("webhook_circuit_breaker:{$this->endpointId}:last_failure");
    }
}