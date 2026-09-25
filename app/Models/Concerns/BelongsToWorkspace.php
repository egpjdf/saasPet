<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToWorkspace
{
    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            $tenantContext = app('tenant');
            
            if (! $tenantContext instanceof \App\Services\Tenant\TenantContext) {
                return;
            }

            $workspaceId = $tenantContext->workspaceId();
            
            if ($workspaceId) {
                $builder->where('workspace_id', $workspaceId);
            }
        });
    }

    public function scopeWithoutWorkspaceScope(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('workspace');
    }

    public function workspace(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Workspace::class, 'workspace_id');
    }
}