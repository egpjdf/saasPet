<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceUserStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceUser extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, HasFactory, SoftDeletes;

    protected $table = 'workspace_users';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'organization_id',
        'role',
        'status',
        'invited_by',
        'invited_at',
        'joined_at',
    ];

    protected $casts = [
        'role' => WorkspaceRole::class,
        'status' => WorkspaceUserStatus::class,
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isActive(): bool
    {
        return $this->status === WorkspaceUserStatus::Active;
    }

    public function isPending(): bool
    {
        return $this->status === WorkspaceUserStatus::Pending;
    }

    public function isRevoked(): bool
    {
        return $this->status === WorkspaceUserStatus::Revoked;
    }

    public function accept(): void
    {
        $this->update([
            'status' => WorkspaceUserStatus::Active,
            'joined_at' => now(),
        ]);
    }

    public function revoke(): void
    {
        $this->update([
            'status' => WorkspaceUserStatus::Revoked,
        ]);
    }
}