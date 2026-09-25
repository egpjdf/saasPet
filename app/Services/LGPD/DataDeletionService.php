<?php

declare(strict_types=1);

namespace App\Services\LGPD;

use App\Models\LGPD\ConsentLog;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataDeletionService
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {}

    public function anonymizeUser(User $user, bool $queueThirdParty = true): array
    {
        $tenantContext = $this->tenantContext;

        if (! $tenantContext->hasOrganization()) {
            throw new \Exception('Organization context required');
        }

        if ((string) $user->organization_id !== $tenantContext->organizationId()) {
            throw new \Exception('User not in current organization');
        }

        if ($tenantContext->hasWorkspace() && (string) $user->workspace_id !== $tenantContext->workspaceId()) {
            throw new \Exception('User not in current workspace');
        }

        $anonymizedData = [
            'name' => 'Usuário Anonimizado ' . Str::random(8),
            'email' => 'deleted_' . Str::random(12) . '@anonymized.local',
            'password' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'email_verified_at' => null,
        ];

        // Anonymize user record
        $user->update($anonymizedData);

        // Anonymize audit logs
        $this->anonymizeAuditLogs($user->id);

        // Anonymize notifications
        $this->anonymizeNotifications($user->id);

        // Queue third-party deletions
        if ($queueThirdParty) {
            $this->queueThirdPartyDeletion($user);
        }

        // Log deletion
        Log::info('LGPD user anonymized', [
            'original_user_id' => $user->id,
            'anonymized_email' => $anonymizedData['email'],
            'organization_id' => $tenantContext->organizationId(),
            'workspace_id' => $tenantContext->workspaceId(),
        ]);

        return [
            'anonymized' => true,
            'anonymized_email' => $anonymizedData['email'],
            'third_party_queued' => $queueThirdParty,
        ];
    }

    public function deleteUserData(User $user, bool $hardDelete = false): array
    {
        if ($hardDelete) {
            // Hard delete - remove all records (use with caution)
            $this->hardDeleteUser($user);
        } else {
            // Soft delete + anonymize
            $this->anonymizeUser($user);
            $user->delete(); // Soft delete
        }

        return [
            'deleted' => true,
            'hard_delete' => $hardDelete,
        ];
    }

    public function anonymizeOrganization(Organization $organization): array
    {
        if (! $this->tenantContext->isPlatformAdmin()) {
            throw new \Exception('Only platform admin can anonymize organizations');
        }

        // Anonymize organization
        $organization->update([
            'name' => 'Organização Anonimizada ' . Str::random(8),
            'slug' => 'deleted-' . Str::random(12),
            'settings' => [],
        ]);

        // Anonymize all users in organization
        $users = User::where('organization_id', $organization->id)->get();
        foreach ($users as $user) {
            $this->anonymizeUser($user, false);
        }

        Log::info('LGPD organization anonymized', [
            'organization_id' => $organization->id,
        ]);

        return ['anonymized' => true];
    }

    private function anonymizeAuditLogs(string $userId): int
    {
        return DB::table('audit_logs')
            ->where('user_id', $userId)
            ->update([
                'user_id' => null,
                'user_name' => 'Usuário Anonimizado',
                'user_email' => 'deleted@anonymized.local',
                'metadata' => DB::raw("jsonb_set(metadata::jsonb, '{user_anonymized}', 'true')"),
                'updated_at' => now(),
            ]);
    }

    private function anonymizeNotifications(string $userId): int
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->update([
                'data' => DB::raw("jsonb_set(data::jsonb, '{user_anonymized}', 'true')"),
                'title' => DB::raw("CASE WHEN title LIKE '%' || (SELECT name FROM users WHERE id = ?) || '%' THEN 'Notificação anonimizada' ELSE title END"),
                'message' => DB::raw("CASE WHEN message LIKE '%' || (SELECT email FROM users WHERE id = ?) || '%' THEN 'Mensagem anonimizada' ELSE message END"),
                'updated_at' => now(),
            ]);
    }

    private function hardDeleteUser(User $user): void
    {
        $userId = $user->id;

        // Delete related records
        DB::table('notifications')->where('user_id', $userId)->delete();
        DB::table('consent_logs')->where('user_id', $userId)->delete();
        DB::table('audit_logs')->where('user_id', $userId)->delete();

        // Force delete user
        $user->forceDelete();
    }

    private function queueThirdPartyDeletion(User $user): void
    {
        // Queue jobs for third-party services
        \App\Jobs\LGPD\DeleteFromStripe::dispatch($user->id)->onQueue('lgpd');
        \App\Jobs\LGPD\DeleteFromPaddle::dispatch($user->id)->onQueue('lgpd');
        \App\Jobs\LGPD\DeleteFromResend::dispatch($user->id)->onQueue('lgpd');
        \App\Jobs\LGPD\DeleteFromSentry::dispatch($user->id)->onQueue('lgpd');
    }
}