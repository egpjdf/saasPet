<?php

declare(strict_types=1);

namespace App\Models\Integrations;

use App\Models\Organization;
use App\Models\Workspace;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebhookDelivery extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'webhook_deliveries';

    protected $fillable = [
        'organization_id',
        'workspace_id',
        'endpoint_id',
        'event',
        'payload',
        'response_code',
        'response_body',
        'attempt',
        'delivered_at',
        'failed_at',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempt' => 'integer',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }

    public function isDelivered(): bool
    {
        return ! is_null($this->delivered_at);
    }

    public function isFailed(): bool
    {
        return ! is_null($this->failed_at);
    }
}