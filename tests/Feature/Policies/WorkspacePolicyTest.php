<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspacePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_any_workspace(): void
    {
        $platformAdmin = User::factory()->create(['role' => UserRole::PlatformAdmin]);
        $workspace = Workspace::factory()->create();

        $this->assertTrue($platformAdmin->can('viewAny', Workspace::class));
        $this->assertTrue($platformAdmin->can('view', $workspace));
    }

    public function test_org_admin_can_view_workspace_in_organization(): void
    {
        $organization = Organization::factory()->create();
        $workspace = Workspace::factory()->create(['organization_id' => $organization->id]);
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);

        $this->assertTrue($orgAdmin->can('view', $workspace));
    }

    public function test_workspace_admin_can_view_own_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $workspaceAdmin = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => UserRole::WorkspaceAdmin,
        ]);

        $this->assertTrue($workspaceAdmin->can('view', $workspace));
    }

    public function test_org_admin_cannot_view_workspace_in_other_organization(): void
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $workspace = Workspace::factory()->create(['organization_id' => $organization2->id]);
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization1->id,
            'role' => UserRole::OrgAdmin,
        ]);

        $this->assertFalse($orgAdmin->can('view', $workspace));
    }

    public function test_workspace_admin_cannot_view_other_workspace(): void
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();
        $workspaceAdmin = User::factory()->create([
            'workspace_id' => $workspace1->id,
            'role' => UserRole::WorkspaceAdmin,
        ]);

        $this->assertFalse($workspaceAdmin->can('view', $workspace2));
    }

    public function test_org_admin_can_create_workspace(): void
    {
        $organization = Organization::factory()->create();
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);

        $this->assertTrue($orgAdmin->can('create', Workspace::class));
    }

    public function test_workspace_admin_cannot_create_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $workspaceAdmin = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => UserRole::WorkspaceAdmin,
        ]);

        $this->assertFalse($workspaceAdmin->can('create', Workspace::class));
    }

    public function test_org_admin_can_update_workspace_in_org(): void
    {
        $organization = Organization::factory()->create();
        $workspace = Workspace::factory()->create(['organization_id' => $organization->id]);
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);

        $this->assertTrue($orgAdmin->can('update', $workspace));
    }

    public function test_workspace_admin_can_update_own_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $workspaceAdmin = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => UserRole::WorkspaceAdmin,
        ]);

        $this->assertTrue($workspaceAdmin->can('update', $workspace));
    }
}