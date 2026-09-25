<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_any_user(): void
    {
        $platformAdmin = User::factory()->create(['role' => UserRole::PlatformAdmin]);
        $user = User::factory()->create();

        $this->assertTrue($platformAdmin->can('viewAny', User::class));
        $this->assertTrue($platformAdmin->can('view', $user));
    }

    public function test_user_can_view_own_profile(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('view', $user));
    }

    public function test_org_admin_can_view_users_in_organization(): void
    {
        $organization = Organization::factory()->create();
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);
        $targetUser = User::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue($orgAdmin->can('view', $targetUser));
    }

    public function test_workspace_admin_can_view_users_in_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $workspaceAdmin = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => UserRole::WorkspaceAdmin,
        ]);
        $targetUser = User::factory()->create(['workspace_id' => $workspace->id]);

        $this->assertTrue($workspaceAdmin->can('view', $targetUser));
    }

    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('update', $user));
    }

    public function test_platform_admin_can_update_any_user(): void
    {
        $platformAdmin = User::factory()->create(['role' => UserRole::PlatformAdmin]);
        $user = User::factory()->create();

        $this->assertTrue($platformAdmin->can('update', $user));
    }

    public function test_org_admin_can_update_user_in_organization(): void
    {
        $organization = Organization::factory()->create();
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);
        $targetUser = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Member,
        ]);

        $this->assertTrue($orgAdmin->can('update', $targetUser));
    }

    public function test_org_admin_cannot_update_platform_admin(): void
    {
        $organization = Organization::factory()->create();
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);
        $platformAdmin = User::factory()->create(['role' => UserRole::PlatformAdmin]);

        $this->assertFalse($orgAdmin->can('update', $platformAdmin));
    }

    public function test_workspace_admin_can_update_member_in_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $workspaceAdmin = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => UserRole::WorkspaceAdmin,
        ]);
        $targetUser = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => UserRole::Member,
        ]);

        $this->assertTrue($workspaceAdmin->can('update', $targetUser));
    }

    public function test_workspace_admin_cannot_update_org_admin(): void
    {
        $workspace = Workspace::factory()->create();
        $workspaceAdmin = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => UserRole::WorkspaceAdmin,
        ]);
        $orgAdmin = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => UserRole::OrgAdmin,
        ]);

        $this->assertFalse($workspaceAdmin->can('update', $orgAdmin));
    }

    public function test_platform_admin_can_delete_user(): void
    {
        $platformAdmin = User::factory()->create(['role' => UserRole::PlatformAdmin]);
        $user = User::factory()->create();

        $this->assertTrue($platformAdmin->can('delete', $user));
    }

    public function test_user_cannot_delete_self(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('delete', $user));
    }

    public function test_org_admin_can_delete_member_in_organization(): void
    {
        $organization = Organization::factory()->create();
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);
        $targetUser = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Member,
        ]);

        $this->assertTrue($orgAdmin->can('delete', $targetUser));
    }

    public function test_org_admin_cannot_delete_another_org_admin(): void
    {
        $organization = Organization::factory()->create();
        $orgAdmin1 = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);
        $orgAdmin2 = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);

        $this->assertFalse($orgAdmin1->can('delete', $orgAdmin2));
    }
}