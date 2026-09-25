<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Organization;
use App\Models\Workspace;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;

class Plan extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'plans';

    protected $fillable = [
        'organization_id',
        'workspace_id',
        'name',
        'slug',
        'description',
        'price_cents',
        'currency',
        'interval',
        'interval_count',
        'features',
        'stripe_price_id',
        'paddle_price_id',
        'is_active',
        'is_featured',
        'sort_order',
        'trial_days',
        'metadata',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'interval_count' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'trial_days' => 'integer',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function priceFormatted(): string
    {
        return number_format($this->price_cents / 100, 2, ',', '.') . ' ' . strtoupper($this->currency);
    }

    public function isMonthly(): bool
    {
        return $this->interval === 'month';
    }

    public function isYearly(): bool
    {
        return $this->interval === 'year';
    }
}