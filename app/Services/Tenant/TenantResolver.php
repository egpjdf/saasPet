<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Organization;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TenantResolver
{
    public const CACHE_TTL = 300; // 5 minutos
    public const CACHE_PREFIX = 'tenant:resolved:';

    public function __construct(
        private PathBasedResolver $pathResolver,
        private SubdomainResolver $subdomainResolver,
    ) {}

    public function resolve(Request $request): ResolvedTenant
    {
        // Platform Admin
        if ($request->is('admin*')) {
            return new ResolvedTenant(
                isPlatformAdmin: true,
            );
        }

        // Tenta resolver por path primeiro
        $resolved = $this->pathResolver->resolve($request);

        if ($resolved) {
            return $resolved;
        }

        // Fallback para subdomain
        return $this->subdomainResolver->resolve($request);
    }

    public function resolveCached(Request $request): ResolvedTenant
    {
        $cacheKey = $this->buildCacheKey($request);

        return Cache::remember($cacheKey, self::CACHE_TTL, fn () => $this->resolve($request));
    }

    private function buildCacheKey(Request $request): string
    {
        return self::CACHE_PREFIX . md5($request->getHost() . $request->path());
    }

    public function clearCache(Request $request): void
    {
        $cacheKey = $this->buildCacheKey($request);
        Cache::forget($cacheKey);
    }
}