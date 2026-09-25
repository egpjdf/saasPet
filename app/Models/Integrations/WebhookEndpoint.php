<?php

declare(strict_types=1);

namespace App\Models\Integrations;

use App\Models\Organization;
use App\Models\Workspace;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebhookEndpoint extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'webhook_endpoints';

    protected $fillable = [
        'organization_id',
        'workspace_id',
        'name',
        'url',
        'secret',
        'events',
        'active',
        'retry_count',
        'last_delivery_at',
        'last_status_code',
        'description',
    ];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
        'retry_count' => 'integer',
        'last_delivery_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'endpoint_id');
    }

    public function handlesEvent(string $event): bool
    {
        return in_array($event, $this->events ?? [], true) || in_array('*', $this->events ?? [], true);
    }
}