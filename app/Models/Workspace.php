<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkspaceStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, HasFactory, SoftDeletes;

    protected $table = 'workspaces';

    protected $fillable = [
        'organization_id',
        'slug',
        'name',
        'settings',
        'status',
    ];

    protected $casts = [
        'settings' => 'array',
        'status' => WorkspaceStatus::class,
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'workspace_id');
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}