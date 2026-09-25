<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use BelongsToOrganization, BelongsToWorkspace, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'workspace_id',
        'organization_id',
        'name',
        'email',
        'password',
        'role',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => UserRole::class,
        'two_factor_recovery_codes' => 'array',
        'password' => 'hashed',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function isPlatformAdmin(): bool
    {
        return $this->role->isPlatformAdmin();
    }

    public function isOrgAdmin(): bool
    {
        return $this->role->isOrgAdmin();
    }

    public function isWorkspaceAdmin(): bool
    {
        return $this->role->isWorkspaceAdmin();
    }

    public function canManageUsers(): bool
    {
        return $this->role->canManageUsers();
    }

    public function canManageBilling(): bool
    {
        return $this->role->canManageBilling();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->two_factor_secret);
    }
}