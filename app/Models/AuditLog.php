<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'organization_id',
        'workspace_id',
        'action',
        'details',
        'ip_address',
        'user_agent',
        'resource_type',
        'resource_id',
        'severity',
        'metadata',
    ];

    protected $casts = [
        'details' => 'array',
        'metadata' => 'array',
        'severity' => 'string',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}