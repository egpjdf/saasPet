<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Organization;
use App\Models\User;
use App\Enums\OrganizationStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;
    private User $orgAdmin;
    private User $workspaceUser;
    private Organization $orgA;
    private Organization $orgB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Platform Admin
        $this->platformAdmin = User::factory()->create([
            'role' => UserRole::PlatformAdmin,
            'email_verified_at' => now(),
        ]);

        // Create Organization A with Org Admin
        $this->orgA = Organization::factory()->create([
            'name' => 'Org A',
            'slug' => 'org-a',
            'status' => OrganizationStatus::Active,
        ]);

        $this->orgAdmin = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'role' => UserRole::OrgAdmin,
            'email_verified_at' => now(),
        ]);

        // Create Organization B with Workspace User
        $this->orgB = Organization::factory()->create([
            'name' => 'Org B',
            'slug' => 'org-b',
            'status' => OrganizationStatus::Active,
        ]);

        $workspaceB = $this->orgB->workspaces()->create([
            'name' => 'Workspace B1',
            'slug' => 'ws-b1',
            'status' => 'active',
        ]);

        $this->workspaceUser = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'workspace_id' => $workspaceB->id,
            'role' => UserRole::Member,
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function platform_admin_can_list_organizations(): void
    {
        Sanctum::actingAs($this->platformAdmin);

        $response = $this->getJson(route('api.admin.organizations.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'attributes' => ['name', 'slug', 'status']],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(2, 'data'); // orgA + orgB
    }

    /** @test */
    public function platform_admin_can_filter_organizations_by_search(): void
    {
        Sanctum::actingAs($this->platformAdmin);

        $response = $this->getJson(route('api.admin.organizations.index', ['search' => 'Org A']));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.name', 'Org A');
    }

    /** @test */
    public function platform_admin_can_filter_organizations_by_status(): void
    {
        Organization::factory()->create([
            'name' => 'Org C Trial',
            'slug' => 'org-c-trial',
            'status' => OrganizationStatus::Trial,
        ]);

        Sanctum::actingAs($this->platformAdmin);

        $response = $this->getJson(route('api.admin.organizations.index', ['status' => OrganizationStatus::Active->value]));

        $response->assertOk()
            ->assertJsonCount(2, 'data'); // orgA + orgB are active
    }

    /** @test */
    public function platform_admin_can_create_organization(): void
    {
        Sanctum::actingAs($this->platformAdmin);

        $response = $this->postJson(route('api.admin.organizations.store'), [
            'name' => 'Nova Org',
            'slug' => 'nova-org',
            'email' => 'nova@org.com',
            'status' => OrganizationStatus::Trial->value,
            'settings' => [
                'primary_color' => '#FF5733',
                'timezone' => 'America/Sao_Paulo',
                'locale' => 'pt_BR',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.name', 'Nova Org')
            ->assertJsonPath('data.attributes.slug', 'nova-org')
            ->assertJsonPath('data.attributes.status', 'trial');

        $this->assertDatabaseHas('organizations', [
            'slug' => 'nova-org',
            'name' => 'Nova Org',
        ]);
    }

    /** @test */
    public function platform_admin_cannot_create_organization_with_duplicate_slug(): void
    {
        Sanctum::actingAs($this->platformAdmin);

        $response = $this->postJson(route('api.admin.organizations.store'), [
            'name' => 'Outra Org',
            'slug' => 'org-a', // Already exists
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function platform_admin_can_view_organization(): void
    {
        Sanctum::actingAs($this->platformAdmin);

        $response = $this->getJson(route('api.admin.organizations.show', $this->orgA));

        $response->assertOk()
            ->assertJsonPath('data.attributes.name', 'Org A')
            ->assertJsonPath('data.attributes.slug', 'org-a');
    }

    /** @test */
    public function platform_admin_can_update_organization(): void
    {
        Sanctum::actingAs($this->platformAdmin);

        $response = $this->putJson(route('api.admin.organizations.update', $this->orgA), [
            'name' => 'Org A Atualizada',
            'settings' => ['primary_color' => '#00FF00'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.attributes.name', 'Org A Atualizada')
            ->assertJsonPath('data.attributes.settings.primary_color', '#00FF00');

        $this->orgA->refresh();
        $this->assertEquals('Org A Atualizada', $this->orgA->name);
        $this->assertEquals('#00FF00', $this->orgA->settings['primary_color']);
    }

    /** @test */
    public function platform_admin_can_soft_delete_organization(): void
    {
        Sanctum::actingAs($this->platformAdmin);

        $response = $this->deleteJson(route('api.admin.organizations.destroy', $this->orgA));

        $response->assertOk()
            ->assertJsonPath('message', 'Organization deleted successfully');

        $this->assertSoftDeleted('organizations', ['id' => $this->orgA->id]);
    }

    /** @test */
    public function platform_admin_can_restore_soft_deleted_organization(): void
    {
        $this->orgA->delete();

        Sanctum::actingAs($this->platformAdmin);

        $response = $this->postJson(route('api.admin.organizations.restore', $this->orgA->id));

        $response->assertOk()
            ->assertJsonPath('message', 'Organization restored successfully');

        $this->orgA->refresh();
        $this->assertNull($this->orgA->deleted_at);
    }

    /** @test */
    public function org_admin_cannot_access_platform_organizations(): void
    {
        Sanctum::actingAs($this->orgAdmin);

        $response = $this->getJson(route('api.admin.organizations.index'));

        $response->assertForbidden();
    }

    /** @test */
    public function workspace_user_cannot_access_platform_organizations(): void
    {
        Sanctum::actingAs($this->workspaceUser);

        $response = $this->getJson(route('api.admin.organizations.index'));

        $response->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_organizations(): void
    {
        $response = $this->getJson(route('api.admin.organizations.index'));

        $response->assertUnauthorized();
    }

    /** @test */
    public function cross_tenant_isolation_org_a_cannot_see_org_b_data_via_policy(): void
    {
        // This test validates that the global scope and policies prevent cross-tenant access
        Sanctum::actingAs($this->orgAdmin);

        // Org Admin tries to access orgB via platform admin route (should be forbidden by middleware)
        $response = $this->getJson(route('api.admin.organizations.show', $this->orgB));

        $response->assertForbidden(); // platform.admin middleware blocks non-platform admins
    }

    /** @test */
    public function pagination_works_correctly(): void
    {
        // Create 20 organizations
        Organization::factory()->count(20)->create(['status' => OrganizationStatus::Active]);

        Sanctum::actingAs($this->platformAdmin);

        $response = $this->getJson(route('api.admin.organizations.index', ['per_page' => 5, 'page' => 1]));

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 22); // 20 + orgA + orgB
    }

    /** @test */
    public function slug_is_normalized_to_lowercase_and_kebab_case(): void
    {
        Sanctum::actingAs($this->platformAdmin);

        $response = $this->postJson(route('api.admin.organizations.store'), [
            'name' => 'Minha Nova Org',
            'slug' => 'Minha_Nova-Org 123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.slug', 'minha-nova-org-123');
    }
}