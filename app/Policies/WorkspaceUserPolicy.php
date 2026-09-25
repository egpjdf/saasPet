<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Auth\Access\Response;

class WorkspaceUserPolicy
{
    public function viewAny(User $user, Workspace $workspace): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isOrgAdmin()) {
            return (string) $user->organization_id === (string) $workspace->organization_id;
        }

        if ($user->isWorkspaceAdmin()) {
            return (string) $user->workspace_id === (string) $workspace->id;
        }

        return (string) $user->workspace_id === (string) $workspace->id;
    }

    public function invite(User $user, Workspace $workspace): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isOrgAdmin()) {
            return (string) $user->organization_id === (string) $workspace->organization_id;
        }

        if ($user->isWorkspaceAdmin()) {
            return (string) $user->workspace_id === (string) $workspace->id;
        }

        return false;
    }

    public function updateRole(User $user, WorkspaceUser $workspaceUser): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isOrgAdmin()) {
            return (string) $user->organization_id === (string) $workspaceUser->organization_id;
        }

        if ($user->isWorkspaceAdmin()) {
            return (string) $user->workspace_id === (string) $workspaceUser->workspace_id
                && $workspaceUser->role !== \App\Enums\WorkspaceRole::Admin; // Cannot demote/promote admins
        }

        return false;
    }

    public function remove(User $user, WorkspaceUser $workspaceUser): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isOrgAdmin()) {
            return (string) $user->organization_id === (string) $workspaceUser->organization_id
                && $workspaceUser->user_id !== $user->id; // Cannot remove self
        }

        if ($user->isWorkspaceAdmin()) {
            return (string) $user->workspace_id === (string) $workspaceUser->workspace_id
                && $workspaceUser->user_id !== $user->id
                && $workspaceUser->role !== \App\Enums\WorkspaceRole::Admin; // Cannot remove admins
        }

        return false;
    }

    public function resendInvite(User $user, WorkspaceUser $workspaceUser): bool
    {
        return $this->invite($user, $workspaceUser->workspace);
    }
}