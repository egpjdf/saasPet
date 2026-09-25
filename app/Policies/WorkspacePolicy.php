<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isOrgAdmin() || $user->isWorkspaceAdmin();
    }

    public function view(User $user, Workspace $workspace): bool
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

    public function create(User $user): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $user->isOrgAdmin();
    }

    public function update(User $user, Workspace $workspace): bool
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

    public function delete(User $user, Workspace $workspace): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isOrgAdmin()) {
            return (string) $user->organization_id === (string) $workspace->organization_id;
        }

        return false;
    }

    public function manageMembers(User $user, Workspace $workspace): bool
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

    public function manageSettings(User $user, Workspace $workspace): bool
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

    public function restore(User $user, Workspace $workspace): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($user->isOrgAdmin()) {
            return (string) $user->organization_id === (string) $workspace->organization_id;
        }

        return false;
    }

    public function forceDelete(User $user, Workspace $workspace): bool
    {
        return $user->isPlatformAdmin();
    }
}