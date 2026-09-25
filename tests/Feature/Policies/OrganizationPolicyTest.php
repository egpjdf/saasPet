<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_any_organization(): void
    {
        $platformAdmin = User::factory()->create(['role' => UserRole::PlatformAdmin]);
        $organization = Organization::factory()->create();

        $this->assertTrue($platformAdmin->can('viewAny', Organization::class));
        $this->assertTrue($platformAdmin->can('view', $organization));
    }

    public function test_org_admin_can_view_own_organization(): void
    {
        $organization = Organization::factory()->create();
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);

        $this->assertTrue($orgAdmin->can('view', $organization));
    }

    public function test_org_admin_cannot_view_other_organization(): void
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization1->id,
            'role' => UserRole::OrgAdmin,
        ]);

        $this->assertFalse($orgAdmin->can('view', $organization2));
    }

    public function test_only_platform_admin_can_create_organization(): void
    {
        $platformAdmin = User::factory()->create(['role' => UserRole::PlatformAdmin]);
        $orgAdmin = User::factory()->create(['role' => UserRole::OrgAdmin]);

        $this->assertTrue($platformAdmin->can('create', Organization::class));
        $this->assertFalse($orgAdmin->can('create', Organization::class));
    }

    public function test_org_admin_can_update_own_organization(): void
    {
        $organization = Organization::factory()->create();
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);

        $this->assertTrue($orgAdmin->can('update', $organization));
    }

    public function test_only_platform_admin_can_delete_organization(): void
    {
        $platformAdmin = User::factory()->create(['role' => UserRole::PlatformAdmin]);
        $orgAdmin = User::factory()->create(['role' => UserRole::OrgAdmin]);

        $this->assertTrue($platformAdmin->can('delete', Organization::factory()->create()));
        $this->assertFalse($orgAdmin->can('delete', Organization::factory()->create()));
    }

    public function test_org_admin_can_manage_workspaces(): void
    {
        $organization = Organization::factory()->create();
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
        ]);

        $this->assertTrue($orgAdmin->can('manageWorkspaces', $organization));
    }
}