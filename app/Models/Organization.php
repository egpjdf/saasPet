<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, HasFactory, SoftDeletes;

    protected $table = 'organizations';

    protected $fillable = [
        'slug',
        'name',
        'settings',
        'status',
        'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'status' => OrganizationStatus::class,
        'trial_ends_at' => 'datetime',
    ];

    protected $dates = [
        'trial_ends_at',
        'deleted_at',
    ];

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'organization_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'organization_id');
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isTrial(): bool
    {
        return $this->status === OrganizationStatus::Trial;
    }

    public function isTrialExpired(): bool
    {
        if (! $this->isTrial() || ! $this->trial_ends_at) {
            return false;
        }

        return $this->trial_ends_at->isPast();
    }
}