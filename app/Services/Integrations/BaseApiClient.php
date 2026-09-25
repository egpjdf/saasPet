<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseApiClient
{
    protected string $baseUrl;
    protected string $accessToken;
    protected ?string $refreshToken = null;
    protected int $tokenExpiresAt = 0;
    protected array $defaultHeaders = [];

    protected int $maxRetries = 3;
    protected int $timeout = 10;
    protected float $backoffMultiplier = 2.0;

    // Circuit breaker
    protected int $failureCount = 0;
    protected int $failureThreshold = 5;
    protected int $circuitOpenUntil = 0;
    protected bool $isHalfOpen = false;

    // Rate limiting
    protected int $maxRequestsPerMinute = 60;
    protected array $requestTimestamps = [];

    public function __construct(
        string $baseUrl,
        string $accessToken,
        ?string $refreshToken = null,
        int $expiresIn = 3600,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->tokenExpiresAt = time() + $expiresIn;
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    public function patch(string $endpoint, array $data = []): array
    {
        return $this->request('PATCH', $endpoint, $data);
    }

    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        // Check circuit breaker
        if ($this->isCircuitOpen()) {
            throw new \Exception('Circuit breaker is open');
        }

        // Check rate limit
        $this->enforceRateLimit();

        // Refresh token if needed
        if ($this->shouldRefreshToken()) {
            $this->refreshAccessToken();
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt <= $this->maxRetries) {
            try {
                $response = $this->makeRequest($method, $endpoint, $data);

                if ($response->successful()) {
                    $this->recordSuccess();
                    return $response->json();
                }

                // Handle rate limiting from API
                if ($response->status() === 429) {
                    $retryAfter = $response->header('Retry-After') ?? 60;
                    sleep($retryAfter);
                    $attempt++;
                    continue;
                }

                // Handle auth errors
                if ($response->status() === 401) {
                    $this->refreshAccessToken();
                    $attempt++;
                    continue;
                }

                $this->recordFailure();

                throw new \Exception("API error: {$response->status()} - {$response->body()}");
            } catch (\Throwable $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt <= $this->maxRetries) {
                    $delay = $this->calculateBackoff($attempt);
                    sleep($delay);
                }
            }
        }

        $this->recordFailure();
        throw $lastException ?? new \Exception('Max retries exceeded');
    }

    protected function makeRequest(string $method, string $endpoint, array $data): \Illuminate\Http\Client\Response
    {
        $url = $this->baseUrl . $endpoint;
        $headers = array_merge($this->defaultHeaders, [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
        ]);

        return Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->retry(0) // We handle retries manually
            ->$method($url, $data);
    }

    protected function enforceRateLimit(): void
    {
        $now = time();
        $this->requestTimestamps = array_filter($this->requestTimestamps, fn ($ts) => $now - $ts < 60);

        if (count($this->requestTimestamps) >= $this->maxRequestsPerMinute) {
            $oldest = min($this->requestTimestamps);
            $waitTime = 60 - ($now - $oldest);
            if ($waitTime > 0) {
                sleep($waitTime);
            }
        }

        $this->requestTimestamps[] = $now;
    }

    protected function shouldRefreshToken(): bool
    {
        return $this->refreshToken && (time() + 60) >= $this->tokenExpiresAt;
    }

    abstract protected function refreshAccessToken(): void;

    protected function calculateBackoff(int $attempt): int
    {
        return (int) (pow($this->backoffMultiplier, $attempt - 1) * 1000);
    }

    protected function isCircuitOpen(): bool
    {
        if ($this->circuitOpenUntil > time()) {
            return true;
        }

        if ($this->isHalfOpen) {
            return false;
        }

        return false;
    }

    protected function recordSuccess(): void
    {
        $this->failureCount = 0;
        $this->isHalfOpen = false;
    }

    protected function recordFailure(): void
    {
        $this->failureCount++;

        if ($this->failureCount >= $this->failureThreshold) {
            $this->circuitOpenUntil = time() + 60; // Open for 60 seconds
            $this->isHalfOpen = true;

            Log::warning('API circuit breaker opened', [
                'base_url' => $this->baseUrl,
                'failure_count' => $this->failureCount,
            ]);
        }
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }
}