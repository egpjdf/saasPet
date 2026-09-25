<?php

declare(strict_types=1);

namespace App\Models\LGPD;

use App\Models\Organization;
use App\Models\Workspace;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetentionPolicy extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'retention_policies';

    protected $fillable = [
        'organization_id',
        'workspace_id',
        'resource_type', // users, notifications, audit_logs, invoices, etc.
        'retention_days',
        'action', // anonymize, delete, archive
        'enabled',
        'description',
    ];

    protected $casts = [
        'retention_days' => 'integer',
        'enabled' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public static function getDefaultPolicies(): array
    {
        return [
            ['resource_type' => 'audit_logs', 'retention_days' => 2555, 'action' => 'anonymize', 'enabled' => true], // 7 years
            ['resource_type' => 'notifications', 'retention_days' => 365, 'action' => 'delete', 'enabled' => true], // 1 year
            ['resource_type' => 'invoices', 'retention_days' => 2555, 'action' => 'archive', 'enabled' => true], // 7 years
            ['resource_type' => 'webhook_deliveries', 'retention_days' => 90, 'action' => 'delete', 'enabled' => true], // 90 days
            ['resource_type' => 'consent_logs', 'retention_days' => 2555, 'action' => 'archive', 'enabled' => true], // 7 years
        ];
    }
}