<?php

declare(strict_types=1);

namespace App\Services\Vault;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VaultClient
{
    private string $address;
    private ?string $token = null;
    private string $mount;
    private string $prefix;
    private bool $tlsVerify;
    private int $timeout;
    private string $namespace;

    public function __construct()
    {
        $this->address = rtrim(config('vault.address'), '/');
        $this->token = config('vault.token');
        $this->mount = config('vault.mount');
        $this->prefix = config('vault.prefix');
        $this->tlsVerify = config('vault.tls_verify');
        $this->timeout = config('vault.timeout');
        $this->namespace = config('vault.namespace');
    }

    /**
     * Authenticate with Vault using AppRole
     */
    public function authenticate(): string
    {
        $roleId = config('vault.role_id');
        $secretId = config('vault.secret_id');

        if (! $roleId || ! $secretId) {
            throw new RuntimeException('Vault AppRole credentials not configured');
        }

        $response = Http::timeout($this->timeout)
            ->withOptions(['verify' => $this->tlsVerify])
            ->post("{$this->address}/v1/auth/approle/login", [
                'role_id' => $roleId,
                'secret_id' => $secretId,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Vault authentication failed: ' . $response->body());
        }

        $this->token = $response->json('auth.client_token');

        return $this->token;
    }

    /**
     * Get token, authenticating if needed
     */
    private function getToken(): string
    {
        if (! $this->token) {
            $this->authenticate();
        }

        return $this->token;
    }

    /**
     * Read a secret from Vault
     */
    public function read(string $path): array
    {
        $cacheKey = "vault:secret:{$path}";

        if (config('vault.cache.enabled')) {
            return Cache::store(config('vault.cache.store'))
                ->remember($cacheKey, config('vault.cache.ttl'), fn () => $this->doRead($path));
        }

        return $this->doRead($path);
    }

    private function doRead(string $path): array
    {
        $response = Http::timeout($this->timeout)
            ->withToken($this->getToken())
            ->withOptions(['verify' => $this->tlsVerify])
            ->when($this->namespace, fn ($req) => $req->withHeaders(['X-Vault-Namespace' => $this->namespace]))
            ->get("{$this->address}/v1/{$this->mount}/data/{$this->prefix}/{$path}");

        if ($response->status() === 404) {
            throw new RuntimeException("Secret not found: {$path}");
        }

        if ($response->failed()) {
            // Token might be expired, try re-authenticating once
            if ($response->status() === 403) {
                $this->authenticate();
                return $this->doRead($path);
            }

            throw new RuntimeException("Vault read failed: {$response->status()} - {$response->body()}");
        }

        return $response->json('data.data') ?? [];
    }

    /**
     * Read multiple secrets at once
     */
    public function readMultiple(array $paths): array
    {
        $results = [];

        foreach ($paths as $path) {
            try {
                $results[$path] = $this->read($path);
            } catch (RuntimeException $e) {
                Log::warning("Failed to read Vault secret: {$path}", ['error' => $e->getMessage()]);
                $results[$path] = [];
            }
        }

        return $results;
    }

    /**
     * Write a secret to Vault
     */
    public function write(string $path, array $data): void
    {
        $response = Http::timeout($this->timeout)
            ->withToken($this->getToken())
            ->withOptions(['verify' => $this->tlsVerify])
            ->when($this->namespace, fn ($req) => $req->withHeaders(['X-Vault-Namespace' => $this->namespace]))
            ->post("{$this->address}/v1/{$this->mount}/data/{$this->prefix}/{$path}", [
                'data' => $data,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Vault write failed: {$response->status()} - {$response->body()}");
        }

        // Invalidate cache
        if (config('vault.cache.enabled')) {
            Cache::store(config('vault.cache.store'))->forget("vault:secret:{$path}");
        }
    }

    /**
     * Patch a secret (merge with existing)
     */
    public function patch(string $path, array $data): void
    {
        $existing = $this->read($path);
        $merged = array_merge($existing, $data);
        $this->write($path, $merged);
    }

    /**
     * Delete a secret
     */
    public function delete(string $path): void
    {
        $response = Http::timeout($this->timeout)
            ->withToken($this->getToken())
            ->withOptions(['verify' => $this->tlsVerify])
            ->when($this->namespace, fn ($req) => $req->withHeaders(['X-Vault-Namespace' => $this->namespace]))
            ->delete("{$this->address}/v1/{$this->mount}/data/{$this->prefix}/{$path}");

        if ($response->failed() && $response->status() !== 404) {
            throw new RuntimeException("Vault delete failed: {$response->status()} - {$response->body()}");
        }

        // Invalidate cache
        if (config('vault.cache.enabled')) {
            Cache::store(config('vault.cache.store'))->forget("vault:secret:{$path}");
        }
    }

    /**
     * List secrets at a path
     */
    public function list(string $path): array
    {
        $response = Http::timeout($this->timeout)
            ->withToken($this->getToken())
            ->withOptions(['verify' => $this->tlsVerify])
            ->when($this->namespace, fn ($req) => $req->withHeaders(['X-Vault-Namespace' => $this->namespace]))
            ->request('LIST', "{$this->address}/v1/{$this->mount}/metadata/{$this->prefix}/{$path}");

        if ($response->failed()) {
            if ($response->status() === 404) {
                return [];
            }
            throw new RuntimeException("Vault list failed: {$response->status()} - {$response->body()}");
        }

        return $response->json('data.keys') ?? [];
    }

    /**
     * Get tenant configuration from Vault
     */
    public function getTenantConfig(string $organizationId): array
    {
        $orgs = $this->list(config('vault.tenant_paths.organizations'));
        
        if (! in_array($organizationId, $orgs)) {
            throw new RuntimeException("Organization not found in Vault: {$organizationId}");
        }

        $orgPath = config('vault.tenant_paths.organizations') . "/{$organizationId}";
        $orgData = $this->read($orgPath);

        $wsPath = str_replace('{organization_id}', $organizationId, config('vault.tenant_paths.workspaces'));
        $workspaces = $this->list($wsPath);

        $wsData = [];
        foreach ($workspaces as $wsId) {
            $wsData[$wsId] = $this->read("{$wsPath}/{$wsId}");
        }

        return [
            'organization' => array_merge(['id' => $organizationId], $orgData),
            'workspaces' => $wsData,
        ];
    }

    /**
     * Check if Vault is healthy
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)
                ->withOptions(['verify' => $this->tlsVerify])
                ->get("{$this->address}/v1/sys/health");

            return $response->successful() || $response->status() === 429; // 429 = sealed but running
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get secret with tenant context
     */
    public function getSecretForTenant(string $secretName, string $organizationId, ?string $workspaceId = null): array
    {
        $path = "tenants/{$organizationId}/secrets/{$secretName}";
        
        if ($workspaceId) {
            $path = "tenants/{$organizationId}/workspaces/{$workspaceId}/secrets/{$secretName}";
        }

        return $this->read($path);
    }
}