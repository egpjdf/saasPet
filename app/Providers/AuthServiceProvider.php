<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Policies\OrganizationPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkspacePolicy;
use App\Policies\WorkspaceUserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Organization::class => OrganizationPolicy::class,
        Workspace::class => WorkspacePolicy::class,
        WorkspaceUser::class => WorkspaceUserPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        $this->definePlatformAdminGates();
        $this->defineOrganizationAdminGates();
        $this->defineWorkspaceAdminGates();
    }

    private function definePlatformAdminGates(): void
    {
        Gate::define('platform.admin', fn (User $user) => $user->isPlatformAdmin());

        Gate::define('platform.organizations.manage', fn (User $user) => $user->isPlatformAdmin());

        Gate::define('platform.organizations.view', fn (User $user) => $user->isPlatformAdmin());

        Gate::define('platform.billing.view', fn (User $user) => $user->isPlatformAdmin());

        Gate::define('platform.billing.manage', fn (User $user) => $user->isPlatformAdmin());

        Gate::define('platform.users.manage', fn (User $user) => $user->isPlatformAdmin());

        Gate::define('platform.workspaces.manage', fn (User $user) => $user->isPlatformAdmin());

        Gate::define('platform.settings.manage', fn (User $user) => $user->isPlatformAdmin());

        Gate::define('platform.impersonate', fn (User $user) => $user->isPlatformAdmin());

        Gate::define('platform.audit.view', fn (User $user) => $user->isPlatformAdmin());
    }

    private function defineOrganizationAdminGates(): void
    {
        Gate::define('organization.admin', fn (User $user) => $user->isOrgAdmin());

        Gate::define('organization.workspaces.manage', function (User $user, Organization $organization) {
            return $user->isOrgAdmin() && (string) $user->organization_id === (string) $organization->id;
        });

        Gate::define('organization.workspaces.view', function (User $user, Organization $organization) {
            return ($user->isOrgAdmin() || $user->isWorkspaceAdmin())
                && (string) $user->organization_id === (string) $organization->id;
        });

        Gate::define('organization.billing.manage', function (User $user, Organization $organization) {
            return $user->canManageBilling() && (string) $user->organization_id === (string) $organization->id;
        });

        Gate::define('organization.billing.view', function (User $user, Organization $organization) {
            return $user->canManageBilling() && (string) $user->organization_id === (string) $organization->id;
        });

        Gate::define('organization.members.manage', function (User $user, Organization $organization) {
            return $user->canManageUsers() && (string) $user->organization_id === (string) $organization->id;
        });

        Gate::define('organization.members.view', function (User $user, Organization $organization) {
            return (string) $user->organization_id === (string) $organization->id;
        });

        Gate::define('organization.settings.manage', function (User $user, Organization $organization) {
            return $user->isOrgAdmin() && (string) $user->organization_id === (string) $organization->id;
        });

        Gate::define('organization.delete', function (User $user, Organization $organization) {
            return $user->isPlatformAdmin();
        });
    }

    private function defineWorkspaceAdminGates(): void
    {
        Gate::define('workspace.admin', fn (User $user) => $user->isWorkspaceAdmin());

        Gate::define('workspace.members.manage', function (User $user, Workspace $workspace) {
            if ($user->isPlatformAdmin() || $user->isOrgAdmin()) {
                return (string) $user->organization_id === (string) $workspace->organization_id;
            }

            return $user->isWorkspaceAdmin() && (string) $user->workspace_id === (string) $workspace->id;
        });

        Gate::define('workspace.members.view', function (User $user, Workspace $workspace) {
            return (string) $user->workspace_id === (string) $workspace->id;
        });

        Gate::define('workspace.settings.manage', function (User $user, Workspace $workspace) {
            if ($user->isPlatformAdmin() || $user->isOrgAdmin()) {
                return (string) $user->organization_id === (string) $workspace->organization_id;
            }

            return $user->isWorkspaceAdmin() && (string) $user->workspace_id === (string) $workspace->id;
        });

        Gate::define('workspace.delete', function (User $user, Workspace $workspace) {
            if ($user->isPlatformAdmin()) {
                return true;
            }

            if ($user->isOrgAdmin()) {
                return (string) $user->organization_id === (string) $workspace->organization_id;
            }

            return false;
        });

        Gate::define('workspace.invite', function (User $user, Workspace $workspace) {
            if ($user->isPlatformAdmin() || $user->isOrgAdmin()) {
                return (string) $user->organization_id === (string) $workspace->organization_id;
            }

            return $user->isWorkspaceAdmin() && (string) $user->workspace_id === (string) $workspace->id;
        });
    }
}