<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isOrgAdmin() || $user->isWorkspaceAdmin();
    }

    public function view(User $currentUser, User $targetUser): bool
    {
        if ($currentUser->isPlatformAdmin()) {
            return true;
        }

        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        if ($currentUser->isOrgAdmin()) {
            return (string) $currentUser->organization_id === (string) $targetUser->organization_id;
        }

        if ($currentUser->isWorkspaceAdmin()) {
            return (string) $currentUser->workspace_id === (string) $targetUser->workspace_id;
        }

        return false;
    }

    public function update(User $currentUser, User $targetUser): bool
    {
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        if ($currentUser->isPlatformAdmin()) {
            return true;
        }

        if ($currentUser->isOrgAdmin()) {
            return (string) $currentUser->organization_id === (string) $targetUser->organization_id
                && $targetUser->role !== \App\Enums\UserRole::PlatformAdmin;
        }

        if ($currentUser->isWorkspaceAdmin()) {
            return (string) $currentUser->workspace_id === (string) $targetUser->workspace_id
                && ! in_array($targetUser->role, [\App\Enums\UserRole::PlatformAdmin, \App\Enums\UserRole::OrgAdmin], true);
        }

        return false;
    }

    public function delete(User $currentUser, User $targetUser): bool
    {
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        if ($currentUser->isPlatformAdmin()) {
            return true;
        }

        if ($currentUser->isOrgAdmin()) {
            return (string) $currentUser->organization_id === (string) $targetUser->organization_id
                && $targetUser->role !== \App\Enums\UserRole::PlatformAdmin
                && $targetUser->role !== \App\Enums\UserRole::OrgAdmin;
        }

        if ($currentUser->isWorkspaceAdmin()) {
            return (string) $currentUser->workspace_id === (string) $targetUser->workspace_id
                && ! in_array($targetUser->role, [\App\Enums\UserRole::PlatformAdmin, \App\Enums\UserRole::OrgAdmin, \App\Enums\UserRole::WorkspaceAdmin], true);
        }

        return false;
    }

    public function manageRoles(User $currentUser, User $targetUser): bool
    {
        if ($currentUser->isPlatformAdmin()) {
            return true;
        }

        if ($currentUser->isOrgAdmin()) {
            return (string) $currentUser->organization_id === (string) $targetUser->organization_id
                && $targetUser->role !== \App\Enums\UserRole::PlatformAdmin;
        }

        if ($currentUser->isWorkspaceAdmin()) {
            return (string) $currentUser->workspace_id === (string) $targetUser->workspace_id
                && ! in_array($targetUser->role, [\App\Enums\UserRole::PlatformAdmin, \App\Enums\UserRole::OrgAdmin], true);
        }

        return false;
    }

    public function impersonate(User $currentUser, User $targetUser): bool
    {
        if (! $currentUser->isPlatformAdmin()) {
            return false;
        }

        return $targetUser->role !== \App\Enums\UserRole::PlatformAdmin;
    }

    public function restore(User $currentUser, User $targetUser): bool
    {
        return $currentUser->isPlatformAdmin();
    }

    public function forceDelete(User $currentUser, User $targetUser): bool
    {
        return $currentUser->isPlatformAdmin();
    }
}