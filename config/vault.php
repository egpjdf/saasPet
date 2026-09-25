<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Vault Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for HashiCorp Vault integration. Vault is used for
    | centralized secret management with automatic rotation.
    |
    */

    'enabled' => env('VAULT_ENABLED', false),

    'address' => env('VAULT_ADDR', 'https://vault.example.com'),

    'token' => env('VAULT_TOKEN'),

    'role_id' => env('VAULT_ROLE_ID'),

    'secret_id' => env('VAULT_SECRET_ID'),

    'namespace' => env('VAULT_NAMESPACE'),

    'mount' => env('VAULT_MOUNT', 'secret'),

    'prefix' => env('VAULT_PREFIX', 'saaspet'),

    'tls_verify' => env('VAULT_TLS_VERIFY', true),

    'timeout' => env('VAULT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Secret Paths
    |--------------------------------------------------------------------------
    |
    | Define the paths to secrets in Vault. These follow the pattern:
    | secret/{prefix}/{category}
    |
    */

    'paths' => [
        'database' => 'database',
        'redis' => 'redis',
        'app' => 'app',
        'jwt' => 'jwt',
        'encryption' => 'encryption',
        'stripe' => 'stripe',
        'paddle' => 'paddle',
        'resend' => 'resend',
        'sentry' => 'sentry',
        'storage' => 'storage',
        'reverb' => 'reverb',
        'mail' => 'mail',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Secret Paths
    |--------------------------------------------------------------------------
    |
    | Paths for tenant-specific secrets. These are namespaced by organization.
    |
    */

    'tenant_paths' => [
        'organizations' => 'tenants/organizations',
        'workspaces' => 'tenants/{organization_id}/workspaces',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Vault responses can be cached to reduce API calls.
    |
    */

    'cache' => [
        'enabled' => env('VAULT_CACHE_ENABLED', true),
        'ttl' => env('VAULT_CACHE_TTL', 300), // 5 minutes
        'store' => env('VAULT_CACHE_STORE', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-reload Configuration
    |--------------------------------------------------------------------------
    |
    | When using Vault Agent sidecar, the application can reload configuration
    | when secrets change.
    |
    */

    'auto_reload' => [
        'enabled' => env('VAULT_AUTO_RELOAD', true),
        'signal' => env('VAULT_RELOAD_SIGNAL', 'SIGHUP'),
    ],

];