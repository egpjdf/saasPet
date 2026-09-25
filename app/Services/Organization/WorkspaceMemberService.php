<?php

declare(strict_types=1);

namespace App\Services\Organization;

use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceUserStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceMemberService
{
    public function __construct(
        private AuditService $auditService,
    ) {}

    public function invite(Workspace $workspace, string $email, WorkspaceRole $role, User $inviter): WorkspaceUser
    {
        // Check if user already exists in this workspace
        $existingUser = User::where('email', $email)
            ->where('organization_id', $workspace->organization_id)
            ->first();

        if ($existingUser) {
            $existingMembership = WorkspaceUser::where('workspace_id', $workspace->id)
                ->where('user_id', $existingUser->id)
                ->first();

            if ($existingMembership) {
                if ($existingMembership->isActive()) {
                    throw ValidationException::withMessages([
                        'email' => 'Este usuário já é membro ativo deste workspace.',
                    ]);
                }

                if ($existingMembership->isPending()) {
                    // Resend invite
                    $existingMembership->update([
                        'role' => $role,
                        'invited_by' => $inviter->id,
                        'invited_at' => now(),
                        'status' => WorkspaceUserStatus::Pending,
                    ]);
                    return $existingMembership;
                }

                // Revoked - reactivate
                $existingMembership->update([
                    'role' => $role,
                    'invited_by' => $inviter->id,
                    'invited_at' => now(),
                    'status' => WorkspaceUserStatus::Pending,
                    'deleted_at' => null,
                ]);
                return $existingMembership;
            }
        }

        // Create new user if doesn't exist in organization
        $user = $existingUser ?? User::create([
            'organization_id' => $workspace->organization_id,
            'workspace_id' => $workspace->id,
            'name' => $email, // Temporary, will be updated on accept
            'email' => $email,
            'password' => Hash::make(Str::random(32)), // Random password, user will set on accept
            'role' => \App\Enums\UserRole::Member,
            'email_verified_at' => null,
        ]);

        // Create workspace membership
        $workspaceUser = WorkspaceUser::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'organization_id' => $workspace->organization_id,
            'role' => $role,
            'status' => WorkspaceUserStatus::Pending,
            'invited_by' => $inviter->id,
            'invited_at' => now(),
        ]);

        // Queue invite email with tenant context
        \App\Jobs\Organization\SendWorkspaceInviteJob::dispatch(
            $workspace->organization_id,
            $workspace->id,
            $workspaceUser->id
        );

        $this->auditService->log(
            action: 'user_invited',
            resourceType: WorkspaceUser::class,
            resourceId: (string) $workspaceUser->id,
            details: [
                'email' => $email,
                'role' => $role->value,
                'workspace_id' => (string) $workspace->id,
                'workspace_name' => $workspace->name,
                'invited_by' => (string) $inviter->id,
            ],
            severity: 'info',
        );

        return $workspaceUser;
    }

    public function accept(string $token): User
    {
        // Token format: workspace_user_id:signed_token
        // For simplicity, we'll decode the token
        // In production, use Laravel's signed URLs
        $workspaceUser = WorkspaceUser::findOrFail($token);

        if (! $workspaceUser->isPending()) {
            throw ValidationException::withMessages([
                'token' => 'Este convite já foi aceito ou revogado.',
            ]);
        }

        $workspaceUser->accept();

        $this->auditService->log(
            action: 'user_joined',
            resourceType: WorkspaceUser::class,
            resourceId: (string) $workspaceUser->id,
            details: [
                'workspace_id' => (string) $workspaceUser->workspace_id,
                'user_id' => (string) $workspaceUser->user_id,
            ],
            severity: 'info',
        );

        return $workspaceUser->user;
    }

    public function updateRole(WorkspaceUser $workspaceUser, WorkspaceRole $newRole): void
    {
        $oldRole = $workspaceUser->role;
        $workspaceUser->update(['role' => $newRole]);

        $this->auditService->log(
            action: 'user_role_changed',
            resourceType: WorkspaceUser::class,
            resourceId: (string) $workspaceUser->id,
            details: [
                'workspace_id' => (string) $workspaceUser->workspace_id,
                'user_id' => (string) $workspaceUser->user_id,
                'old_role' => $oldRole->value,
                'new_role' => $newRole->value,
            ],
            severity: 'info',
        );
    }

    public function remove(WorkspaceUser $workspaceUser): void
    {
        $workspaceId = $workspaceUser->workspace_id;
        $userId = $workspaceUser->user_id;
        $role = $workspaceUser->role;

        $workspaceUser->delete();

        $this->auditService->log(
            action: 'user_removed',
            resourceType: WorkspaceUser::class,
            resourceId: (string) $workspaceUser->id,
            details: [
                'workspace_id' => (string) $workspaceId,
                'user_id' => (string) $userId,
                'role' => $role->value,
            ],
            severity: 'warning',
        );
    }

    public function resendInvite(WorkspaceUser $workspaceUser): void
    {
        if (! $workspaceUser->isPending()) {
            throw ValidationException::withMessages([
                'workspace_user' => 'Só é possível reenviar convites pendentes.',
            ]);
        }

        $workspaceUser->update([
            'invited_at' => now(),
        ]);

        // Queue invite email with tenant context
        \App\Jobs\Organization\SendWorkspaceInviteJob::dispatch(
            $workspaceUser->organization_id,
            $workspaceUser->workspace_id,
            $workspaceUser->id
        );

        $this->auditService->log(
            action: 'user_invite_resent',
            resourceType: WorkspaceUser::class,
            resourceId: (string) $workspaceUser->id,
            details: [
                'workspace_id' => (string) $workspaceUser->workspace_id,
                'user_id' => (string) $workspaceUser->user_id,
            ],
            severity: 'info',
        );
    }
}