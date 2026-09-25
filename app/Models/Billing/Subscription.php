<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Organization;
use App\Models\Workspace;
use App\Models\User;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'subscriptions';

    protected $fillable = [
        'organization_id',
        'workspace_id',
        'user_id',
        'plan_id',
        'name',
        'stripe_id',
        'paddle_id',
        'stripe_status',
        'paddle_status',
        'stripe_price_id',
        'paddle_price_id',
        'quantity',
        'trial_ends_at',
        'ends_at',
        'cancelled_at',
        'cancels_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancels_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $dates = [
        'trial_ends_at',
        'ends_at',
        'cancelled_at',
        'cancels_at',
        'deleted_at',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        return in_array($this->stripe_status, ['active', 'trialing'], true)
            || in_array($this->paddle_status, ['active', 'trialing'], true);
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isCancelled(): bool
    {
        return ! is_null($this->cancelled_at);
    }

    public function onGracePeriod(): bool
    {
        return $this->cancels_at && $this->cancels_at->isFuture();
    }

    public function trialDaysRemaining(): int
    {
        if (! $this->trial_ends_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }
}