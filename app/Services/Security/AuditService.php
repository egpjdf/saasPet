<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public const ACTIONS = [
        // Auth
        'login', 'logout', 'login_failed', 'password_changed',
        '2fa_enabled', '2fa_disabled', '2fa_recovery_used',
        'impersonation_started', 'impersonation_ended',

        // Tenant
        'organization_created', 'organization_updated', 'organization_deleted', 'organization_restored',
        'workspace_created', 'workspace_updated', 'workspace_deleted', 'workspace_restored',
        'user_invited', 'user_joined', 'user_removed', 'user_role_changed',

        // Billing
        'subscription_created', 'subscription_updated', 'subscription_cancelled',
        'invoice_created', 'invoice_paid', 'invoice_refunded',
        'payment_method_added', 'payment_method_removed',

        // Data
        'data_export_requested', 'data_export_completed', 'data_download',
        'data_deletion_requested', 'data_deletion_completed',
        'data_rectified', 'consent_granted', 'consent_revoked',

        // Security
        'permission_changed', 'api_token_created', 'api_token_revoked',
        'webhook_endpoint_created', 'webhook_endpoint_updated',
        'webhook_delivery_failed', 'webhook_signature_invalid',

        // AI
        'ai_agent_invoked', 'ai_embedding_generated', 'ai_data_exported',

        // System
        'feature_flag_changed', 'maintenance_mode_toggled',
    ];

    public function log(
        string $action,
        ?string $resourceType = null,
        ?string $resourceId = null,
        array $details = [],
        string $severity = 'info',
        array $metadata = [],
    ): AuditLog {
        $user = Auth::user();
        $request = request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'organization_id' => $user?->organization_id,
            'workspace_id' => $user?->workspace_id,
            'action' => $action,
            'details' => $details,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'severity' => $severity,
            'metadata' => array_merge($metadata, [
                'request_id' => $request?->id(),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
            ]),
        ]);
    }

    public function logAuth(string $action, ?User $user = null, array $details = [], string $severity = 'info'): AuditLog
    {
        $request = request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'organization_id' => $user?->organization_id,
            'workspace_id' => $user?->workspace_id,
            'action' => $action,
            'details' => $details,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'resource_type' => User::class,
            'resource_id' => $user?->id ? (string) $user->id : null,
            'severity' => $severity,
            'metadata' => [
                'request_id' => $request->id(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ],
        ]);
    }

    public function logImpersonation(string $action, User $impersonator, User $target, array $details = []): AuditLog
    {
        return $this->log(
            action: $action,
            resourceType: User::class,
            resourceId: (string) $target->id,
            details: array_merge($details, [
                'impersonator_id' => (string) $impersonator->id,
                'impersonator_role' => $impersonator->role->value,
                'target_role' => $target->role->value,
            ]),
            severity: 'warning',
        );
    }

    public function validateAction(string $action): bool
    {
        return in_array($action, self::ACTIONS, true);
    }
}