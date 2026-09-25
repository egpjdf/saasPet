<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array read(string $path)
 * @method static array readMultiple(array $paths)
 * @method static void write(string $path, array $data)
 * @method static void patch(string $path, array $data)
 * @method static void delete(string $path)
 * @method static array list(string $path)
 * @method static array getTenantConfig(string $organizationId)
 * @method static bool isHealthy()
 * @method static array getSecretForTenant(string $secretName, string $organizationId, ?string $workspaceId = null)
 *
 * @see \App\Services\Vault\VaultClient
 */
class Vault extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'vault';
    }
}