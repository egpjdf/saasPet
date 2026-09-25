<?php

declare(strict_types=1);

namespace App\Services\Vault;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class VaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/vault.php', 'vault');

        $this->app->singleton(VaultClient::class, function ($app) {
            return new VaultClient();
        });

        $this->app->alias(VaultClient::class, 'vault');
    }

    public function boot(): void
    {
        if (! config('vault.enabled')) {
            return;
        }

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/vault.php' => config_path('vault.php'),
        ], 'vault-config');

        // Load secrets into config at boot time
        $this->loadSecretsIntoConfig();
    }

    private function loadSecretsIntoConfig(): void
    {
        try {
            $vault = $this->app->make(VaultClient::class);

            if (! $vault->isHealthy()) {
                \Log::warning('Vault is not healthy, skipping secret loading');
                return;
            }

            $paths = config('vault.paths');

            foreach ($paths as $key => $path) {
                try {
                    $secrets = $vault->read($path);
                    $this->setConfigFromSecrets($key, $secrets);
                } catch (\RuntimeException $e) {
                    \Log::warning("Failed to load Vault secret: {$path}", ['error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to load secrets from Vault', ['error' => $e->getMessage()]);
        }
    }

    private function setConfigFromSecrets(string $category, array $secrets): void
    {
        $prefix = strtoupper($category) . '_';

        foreach ($secrets as $key => $value) {
            $configKey = match ($category) {
                'database' => match ($key) {
                    'host' => 'database.connections.pgsql.host',
                    'port' => 'database.connections.pgsql.port',
                    'database' => 'database.connections.pgsql.database',
                    'username' => 'database.connections.pgsql.username',
                    'password' => 'database.connections.pgsql.password',
                    default => null,
                },
                'redis' => match ($key) {
                    'host' => 'database.redis.client.host',
                    'password' => 'database.redis.client.password',
                    'port' => 'database.redis.client.port',
                    default => null,
                },
                'app' => match ($key) {
                    'key' => 'app.key',
                    default => null,
                },
                'jwt' => match ($key) {
                    'secret' => 'jwt.secret',
                    default => null,
                },
                'encryption' => match ($key) {
                    'key' => 'app.encryption_key',
                    default => null,
                },
                'stripe' => match ($key) {
                    'key' => 'services.stripe.key',
                    'secret' => 'services.stripe.secret',
                    'webhook_secret' => 'services.stripe.webhook_secret',
                    default => null,
                },
                'paddle' => match ($key) {
                    'vendor_id' => 'services.paddle.vendor_id',
                    'api_key' => 'services.paddle.api_key',
                    'webhook_secret' => 'services.paddle.webhook_secret',
                    default => null,
                },
                'resend' => match ($key) {
                    'api_key' => 'services.resend.key',
                    default => null,
                },
                'sentry' => match ($key) {
                    'dsn' => 'services.sentry.dsn',
                    default => null,
                },
                'storage' => match ($key) {
                    'access_key' => 'filesystems.disks.s3.key',
                    'secret_key' => 'filesystems.disks.s3.secret',
                    'region' => 'filesystems.disks.s3.region',
                    'bucket' => 'filesystems.disks.s3.bucket',
                    'endpoint' => 'filesystems.disks.s3.endpoint',
                    default => null,
                },
                'reverb' => match ($key) {
                    'app_id' => 'broadcasting.connections.reverb.app_id',
                    'app_key' => 'broadcasting.connections.reverb.key',
                    'app_secret' => 'broadcasting.connections.reverb.secret',
                    'host' => 'broadcasting.connections.reverb.host',
                    'port' => 'broadcasting.connections.reverb.port',
                    'scheme' => 'broadcasting.connections.reverb.scheme',
                    default => null,
                },
                'mail' => match ($key) {
                    'from_address' => 'mail.from.address',
                    'from_name' => 'mail.from.name',
                    default => null,
                },
                default => null,
            };

            if ($configKey) {
                Config::set($configKey, $value);
            }
        }
    }
}