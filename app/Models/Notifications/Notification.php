<?php

declare(strict_types=1);

namespace App\Models\Notifications;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'organization_id',
        'workspace_id',
        'type',
        'channel',
        'title',
        'message',
        'data',
        'read_at',
        'archived_at',
        'unsubscribe_token',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
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

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function markAsArchived(): void
    {
        $this->update(['archived_at' => now()]);
    }

    public function isRead(): bool
    {
        return ! is_null($this->read_at);
    }

    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    public function generateUnsubscribeToken(): string
    {
        $token = \Illuminate\Support\Str::random(64);
        $this->update(['unsubscribe_token' => $token]);
        return $token;
    }
}