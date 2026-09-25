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
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'organization_id',
        'workspace_id',
        'user_id',
        'subscription_id',
        'stripe_id',
        'paddle_id',
        'number',
        'status',
        'currency',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'amount_paid_cents',
        'amount_due_cents',
        'billing_reason',
        'period_start',
        'period_end',
        'due_date',
        'paid_at',
        'hosted_invoice_url',
        'invoice_pdf',
        'metadata',
    ];

    protected $casts = [
        'subtotal_cents' => 'integer',
        'tax_cents' => 'integer',
        'total_cents' => 'integer',
        'amount_paid_cents' => 'integer',
        'amount_due_cents' => 'integer',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function totalFormatted(): string
    {
        return number_format($this->total_cents / 100, 2, ',', '.') . ' ' . strtoupper($this->currency);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}