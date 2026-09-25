<?php

declare(strict_types=1);

namespace Tests\Feature\Organization;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\WorkspaceStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;
    private User $orgAdminA;
    private User $orgAdminB;
    private User $workspaceAdminA;
    private User $memberA;
    private Organization $orgA;
    private Organization $orgB;
    private Workspace $wsA1;
    private Workspace $wsA2;
    private Workspace $wsB1;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Platform Admin
        $this->platformAdmin = User::factory()->create([
            'role' => UserRole::PlatformAdmin,
            'email_verified_at' => now(),
        ]);

        // Create Organization A
        $this->orgA = Organization::factory()->create([
            'name' => 'Org A',
            'slug' => 'org-a',
            'status' => 'active',
        ]);

        // Create Organization B
        $this->orgB = Organization::factory()->create([
            'name' => 'Org B',
            'slug' => 'org-b',
            'status' => 'active',
        ]);

        // Create workspaces
        $this->wsA1 = $this->orgA->workspaces()->create([
            'name' => 'Workspace A1',
            'slug' => 'ws-a1',
            'status' => 'active',
        ]);

        $this->wsA2 = $this->orgA->workspaces()->create([
            'name' => 'Workspace A2',
            'slug' => 'ws-a2',
            'status' => 'active',
        ]);

        $this->wsB1 = $this->orgB->workspaces()->create([
            'name' => 'Workspace B1',
            'slug' => 'ws-b1',
            'status' => 'active',
        ]);

        // Create users
        $this->orgAdminA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'workspace_id' => $this->wsA1->id,
            'role' => UserRole::OrgAdmin,
            'email_verified_at' => now(),
        ]);

        $this->orgAdminB = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'workspace_id' => $this->wsB1->id,
            'role' => UserRole::OrgAdmin,
            'email_verified_at' => now(),
        ]);

        $this->workspaceAdminA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'workspace_id' => $this->wsA1->id,
            'role' => UserRole::WorkspaceAdmin,
            'email_verified_at' => now(),
        ]);

        $this->memberA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'workspace_id' => $this->wsA1->id,
            'role' => UserRole::Member,
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function org_admin_can_list_workspaces_in_their_org(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->getJson(route('api.organization.workspaces.index', $this->orgA));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'attributes' => ['name', 'slug', 'status']],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(2, 'data'); // wsA1 + wsA2
    }

    /** @test */
    public function org_admin_cannot_list_workspaces_in_other_org(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->getJson(route('api.organization.workspaces.index', $this->orgB));

        $response->assertForbidden();
    }

    /** @test */
    public function platform_admin_can_list_workspaces_in_any_org(): void
    {
        Sanctum::actingAs($this->platformAdmin);

        $response = $this->getJson(route('api.organization.workspaces.index', $this->orgA));

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $response = $this->getJson(route('api.organization.workspaces.index', $this->orgB));

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function workspace_admin_can_list_workspaces_in_their_org(): void
    {
        Sanctum::actingAs($this->workspaceAdminA);

        $response = $this->getJson(route('api.organization.workspaces.index', $this->orgA));

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function member_cannot_list_workspaces(): void
    {
        Sanctum::actingAs($this->memberA);

        $response = $this->getJson(route('api.organization.workspaces.index', $this->orgA));

        $response->assertForbidden();
    }

    /** @test */
    public function org_admin_can_create_workspace_in_their_org(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->postJson(route('api.organization.workspaces.store', $this->orgA), [
            'name' => 'Novo Workspace',
            'slug' => 'novo-ws',
            'email' => 'ws@org-a.com',
            'status' => WorkspaceStatus::Active->value,
            'settings' => [
                'timezone' => 'America/Sao_Paulo',
                'locale' => 'pt_BR',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.name', 'Novo Workspace')
            ->assertJsonPath('data.attributes.slug', 'novo-ws')
            ->assertJsonPath('data.attributes.status', 'active');

        $this->assertDatabaseHas('workspaces', [
            'organization_id' => $this->orgA->id,
            'slug' => 'novo-ws',
        ]);
    }

    /** @test */
    public function org_admin_cannot_create_workspace_in_other_org(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->postJson(route('api.organization.workspaces.store', $this->orgB), [
            'name' => 'Hack Workspace',
            'slug' => 'hack-ws',
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function platform_admin_can_create_workspace_in_any_org(): void
    {
        Sanctum::actingAs($this->platformAdmin);

        $response = $this->postJson(route('api.organization.workspaces.store', $this->orgB), [
            'name' => 'Platform WS',
            'slug' => 'platform-ws',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.organization_id', (string) $this->orgB->id);
    }

    /** @test */
    public function org_admin_cannot_create_workspace_with_duplicate_slug_in_same_org(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->postJson(route('api.organization.workspaces.store', $this->orgA), [
            'name' => 'Duplicate',
            'slug' => 'ws-a1', // Already exists in orgA
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function org_admin_can_view_workspace_in_their_org(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->getJson(route('api.organization.workspaces.show', [$this->orgA, $this->wsA1]));

        $response->assertOk()
            ->assertJsonPath('data.attributes.name', 'Workspace A1')
            ->assertJsonPath('data.attributes.slug', 'ws-a1');
    }

    /** @test */
    public function org_admin_cannot_view_workspace_in_other_org(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->getJson(route('api.organization.workspaces.show', [$this->orgB, $this->wsB1]));

        $response->assertForbidden();
    }

    /** @test */
    public function workspace_admin_can_view_their_own_workspace(): void
    {
        Sanctum::actingAs($this->workspaceAdminA);

        $response = $this->getJson(route('api.organization.workspaces.show', [$this->orgA, $this->wsA1]));

        $response->assertOk();
    }

    /** @test */
    public function workspace_admin_cannot_view_other_workspace_in_same_org(): void
    {
        Sanctum::actingAs($this->workspaceAdminA);

        $response = $this->getJson(route('api.organization.workspaces.show', [$this->orgA, $this->wsA2]));

        $response->assertForbidden();
    }

    /** @test */
    public function org_admin_can_update_workspace_in_their_org(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->putJson(route('api.organization.workspaces.update', [$this->orgA, $this->wsA1]), [
            'name' => 'Workspace A1 Updated',
            'settings' => ['timezone' => 'UTC'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.attributes.name', 'Workspace A1 Updated')
            ->assertJsonPath('data.attributes.settings.timezone', 'UTC');

        $this->wsA1->refresh();
        $this->assertEquals('Workspace A1 Updated', $this->wsA1->name);
    }

    /** @test */
    public function org_admin_can_soft_delete_workspace_in_their_org(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->deleteJson(route('api.organization.workspaces.destroy', [$this->orgA, $this->wsA2]));

        $response->assertOk()
            ->assertJsonPath('message', 'Workspace deleted successfully');

        $this->assertSoftDeleted('workspaces', ['id' => $this->wsA2->id]);
    }

    /** @test */
    public function org_admin_can_restore_soft_deleted_workspace(): void
    {
        $this->wsA2->delete();

        Sanctum::actingAs($this->orgAdminA);

        $response = $this->postJson(route('api.organization.workspaces.restore', [$this->orgA, $this->wsA2->id]));

        $response->assertOk()
            ->assertJsonPath('message', 'Workspace restored successfully');

        $this->wsA2->refresh();
        $this->assertNull($this->wsA2->deleted_at);
    }

    /** @test */
    public function workspace_admin_can_update_their_own_workspace(): void
    {
        Sanctum::actingAs($this->workspaceAdminA);

        $response = $this->putJson(route('api.organization.workspaces.update', [$this->orgA, $this->wsA1]), [
            'name' => 'Updated by WS Admin',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.attributes.name', 'Updated by WS Admin');
    }

    /** @test */
    public function workspace_admin_cannot_delete_workspace(): void
    {
        Sanctum::actingAs($this->workspaceAdminA);

        $response = $this->deleteJson(route('api.organization.workspaces.destroy', [$this->orgA, $this->wsA1]));

        $response->assertForbidden();
    }

    /** @test */
    public function member_cannot_access_workspace_crud(): void
    {
        Sanctum::actingAs($this->memberA);

        $response = $this->getJson(route('api.organization.workspaces.index', $this->orgA));
        $response->assertForbidden();

        $response = $this->postJson(route('api.organization.workspaces.store', $this->orgA), ['name' => 'Test', 'slug' => 'test']);
        $response->assertForbidden();

        $response = $this->getJson(route('api.organization.workspaces.show', [$this->orgA, $this->wsA1]));
        $response->assertForbidden();

        $response = $this->putJson(route('api.organization.workspaces.update', [$this->orgA, $this->wsA1]), ['name' => 'Test']);
        $response->assertForbidden();

        $response = $this->deleteJson(route('api.organization.workspaces.destroy', [$this->orgA, $this->wsA1]));
        $response->assertForbidden();
    }

    /** @test */
    public function cross_tenant_isolation_workspace_a_cannot_see_workspace_b(): void
    {
        Sanctum::actingAs($this->workspaceAdminA);

        // Workspace Admin of wsA1 tries to access wsA2 (same org, different ws)
        $response = $this->getJson(route('api.organization.workspaces.show', [$this->orgA, $this->wsA2]));
        $response->assertForbidden();

        // Workspace Admin of wsA1 tries to access wsB1 (different org)
        $response = $this->getJson(route('api.organization.workspaces.show', [$this->orgB, $this->wsB1]));
        $response->assertForbidden();
    }

    /** @test */
    public function slug_is_normalized_to_lowercase_and_kebab_case(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->postJson(route('api.organization.workspaces.store', $this->orgA), [
            'name' => 'Meu Novo WS',
            'slug' => 'Meu_Novo-WS 123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.slug', 'meu-novo-ws-123');
    }

    /** @test */
    public function pagination_works_correctly(): void
    {
        $this->orgA->workspaces()->createMany(
            Workspace::factory()->count(20)->make(['organization_id' => $this->orgA->id])->toArray()
        );

        Sanctum::actingAs($this->orgAdminA);

        $response = $this->getJson(route('api.organization.workspaces.index', [$this->orgA, 'per_page' => 5, 'page' => 1]));

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 22); // 20 + wsA1 + wsA2
    }
}