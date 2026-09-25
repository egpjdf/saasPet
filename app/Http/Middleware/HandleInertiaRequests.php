<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Response;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        return array_merge(parent::share($request), [
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role->value,
                    'two_factor_enabled' => $request->user()->hasTwoFactorEnabled(),
                ] : null,
            ],
            'tenant' => [
                'organization_id' => $tenantContext->organizationId(),
                'workspace_id' => $tenantContext->workspaceId(),
                'is_platform_admin' => $tenantContext->isPlatformAdmin(),
                'organization' => $request->attributes->get('organization'),
                'workspace' => $request->attributes->get('workspace'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}