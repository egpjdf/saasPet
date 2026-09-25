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

class NotificationPreference extends Model
{
    use HasFactory, HasTenantContext, SoftDeletes;

    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',
        'organization_id',
        'workspace_id',
        'channel', // mail, sms, push, in_app, reverb
        'type', // welcome, password_reset, email_verification, 2fa_enabled, billing_invoice, billing_failed, invitation, mention, etc.
        'enabled',
        'frequency', // immediate, daily_digest, weekly_digest
    ];

    protected $casts = [
        'enabled' => 'boolean',
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

    public static function getDefaultPreferences(): array
    {
        return [
            ['channel' => 'mail', 'type' => 'welcome', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'mail', 'type' => 'password_reset', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'mail', 'type' => 'email_verification', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'mail', 'type' => '2fa_enabled', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'mail', 'type' => 'billing_invoice', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'mail', 'type' => 'billing_failed', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'mail', 'type' => 'invitation', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'mail', 'type' => 'mention', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'in_app', 'type' => 'welcome', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'in_app', 'type' => 'mention', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'in_app', 'type' => 'invitation', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'reverb', 'type' => 'mention', 'enabled' => true, 'frequency' => 'immediate'],
            ['channel' => 'reverb', 'type' => 'invitation', 'enabled' => true, 'frequency' => 'immediate'],
        ];
    }
}