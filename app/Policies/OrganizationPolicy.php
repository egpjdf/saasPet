<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin() || $user->isOrgAdmin();
    }

    public function view(User $user, Organization $organization): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return (string) $user->organization_id === (string) $organization->id;
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, Organization $organization): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if (! $user->isOrgAdmin()) {
            return false;
        }

        return (string) $user->organization_id === (string) $organization->id;
    }

    public function delete(User $user, Organization $organization): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return false;
    }

    public function manageWorkspaces(User $user, Organization $organization): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if (! $user->isOrgAdmin()) {
            return false;
        }

        return (string) $user->organization_id === (string) $organization->id;
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if (! $user->isOrgAdmin()) {
            return false;
        }

        return (string) $user->organization_id === (string) $organization->id;
    }

    public function manageBilling(User $user, Organization $organization): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if (! $user->canManageBilling()) {
            return false;
        }

        return (string) $user->organization_id === (string) $organization->id;
    }

    public function viewBilling(User $user, Organization $organization): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if (! $user->canManageBilling()) {
            return false;
        }

        return (string) $user->organization_id === (string) $organization->id;
    }

    public function restore(User $user, Organization $organization): bool
    {
        return $user->isPlatformAdmin();
    }

    public function forceDelete(User $user, Organization $organization): bool
    {
        return $user->isPlatformAdmin();
    }
}