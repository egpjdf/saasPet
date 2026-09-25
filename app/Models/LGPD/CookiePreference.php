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

class CookiePreference extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'cookie_preferences';

    protected $fillable = [
        'user_id',
        'organization_id',
        'workspace_id',
        'session_id',
        'essential',
        'analytics',
        'marketing',
        'preferences',
        'consented_at',
        'version',
    ];

    protected $casts = [
        'essential' => 'boolean',
        'analytics' => 'boolean',
        'marketing' => 'boolean',
        'preferences' => 'boolean',
        'consented_at' => 'datetime',
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
}