<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

trait BelongsToOrganization
{
    protected static function booted(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            $tenantContext = app('tenant');
            
            if (! $tenantContext instanceof \App\Services\Tenant\TenantContext) {
                return;
            }

            $organizationId = $tenantContext->organizationId();
            
            if ($organizationId) {
                $builder->where('organization_id', $organizationId);
            }
        });
    }

    public function scopeWithoutOrganizationScope(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('organization');
    }

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'organization_id');
    }
}