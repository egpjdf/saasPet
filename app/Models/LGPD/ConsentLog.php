<?php

declare(strict_types=1);

namespace App\Models\LGPD;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsentLog extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'consent_logs';

    protected $fillable = [
        'user_id',
        'organization_id',
        'workspace_id',
        'purpose',
        'legal_basis',
        'granted_at',
        'revoked_at',
        'ip',
        'user_agent',
        'version',
        'metadata',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at);
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }
}