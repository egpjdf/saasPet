<?php

declare(strict_types=1);

namespace Tests\Feature\Organization;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceUserStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;
    private User $orgAdminA;
    private User $workspaceAdminA;
    private User $memberA;
    private Organization $orgA;
    private Organization $orgB;
    private Workspace $wsA1;
    private Workspace $wsA2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Platform Admin
        $this->platformAdmin = User::factory()->create([
            'role' => UserRole::PlatformAdmin,
            'email_verified_at' => now(),
        ]);

        // Create Organizations
        $this->orgA = Organization::factory()->create([
            'name' => 'Org A',
            'slug' => 'org-a',
            'status' => 'active',
        ]);

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

        // Create users
        $this->orgAdminA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'workspace_id' => $this->wsA1->id,
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
    public function org_admin_can_invite_user_to_workspace(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->postJson(route('api.organization.workspaces.members.store', [$this->orgA, $this->wsA1]), [
            'email' => 'newuser@org-a.com',
            'role' => WorkspaceRole::Member->value,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.role', 'member')
            ->assertJsonPath('data.attributes.status', 'pending');

        $this->assertDatabaseHas('workspace_users', [
            'workspace_id' => $this->wsA1->id,
            'role' => WorkspaceRole::Member->value,
            'status' => WorkspaceUserStatus::Pending->value,
        ]);
    }

    /** @test */
    public function org_admin_can_invite_user_with_admin_role(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->postJson(route('api.organization.workspaces.members.store', [$this->orgA, $this->wsA1]), [
            'email' => 'admin@org-a.com',
            'role' => WorkspaceRole::Admin->value,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.role', 'admin');
    }

    /** @test */
    public function org_admin_cannot_invite_user_to_other_org_workspace(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $wsB1 = $this->orgB->workspaces()->create([
            'name' => 'Workspace B1',
            'slug' => 'ws-b1',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('api.organization.workspaces.members.store', [$this->orgB, $wsB1]), [
            'email' => 'hack@org-b.com',
            'role' => WorkspaceRole::Member->value,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function workspace_admin_can_invite_to_their_workspace(): void
    {
        Sanctum::actingAs($this->workspaceAdminA);

        $response = $this->postJson(route('api.organization.workspaces.members.store', [$this->orgA, $this->wsA1]), [
            'email' => 'wsadmin@org-a.com',
            'role' => WorkspaceRole::Member->value,
        ]);

        $response->assertCreated();
    }

    /** @test */
    public function workspace_admin_cannot_invite_to_other_workspace_in_same_org(): void
    {
        Sanctum::actingAs($this->workspaceAdminA);

        $response = $this->postJson(route('api.organization.workspaces.members.store', [$this->orgA, $this->wsA2]), [
            'email' => 'hack@org-a.com',
            'role' => WorkspaceRole::Member->value,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function member_cannot_invite_users(): void
    {
        Sanctum::actingAs($this->memberA);

        $response = $this->postJson(route('api.organization.workspaces.members.store', [$this->orgA, $this->wsA1]), [
            'email' => 'hack@org-a.com',
            'role' => WorkspaceRole::Member->value,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function org_admin_can_list_workspace_members(): void
    {
        // Create some members
        WorkspaceUser::factory()->count(3)->create([
            'workspace_id' => $this->wsA1->id,
            'organization_id' => $this->orgA->id,
            'status' => WorkspaceUserStatus::Active,
        ]);

        Sanctum::actingAs($this->orgAdminA);

        $response = $this->getJson(route('api.organization.workspaces.members.index', [$this->orgA, $this->wsA1]));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function org_admin_can_update_member_role(): void
    {
        $workspaceUser = WorkspaceUser::factory()->create([
            'workspace_id' => $this->wsA1->id,
            'organization_id' => $this->orgA->id,
            'role' => WorkspaceRole::Member,
            'status' => WorkspaceUserStatus::Active,
        ]);

        Sanctum::actingAs($this->orgAdminA);

        $response = $this->putJson(route('api.organization.workspaces.members.update', [$this->orgA, $this->wsA1, $workspaceUser]), [
            'role' => WorkspaceRole::Admin->value,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.attributes.role', 'admin');

        $workspaceUser->refresh();
        $this->assertEquals(WorkspaceRole::Admin, $workspaceUser->role);
    }

    /** @test */
    public function org_admin_cannot_demote_admin_via_workspace_admin(): void
    {
        $adminUser = WorkspaceUser::factory()->create([
            'workspace_id' => $this->wsA1->id,
            'organization_id' => $this->orgA->id,
            'role' => WorkspaceRole::Admin,
            'status' => WorkspaceUserStatus::Active,
        ]);

        Sanctum::actingAs($this->workspaceAdminA);

        $response = $this->putJson(route('api.organization.workspaces.members.update', [$this->orgA, $this->wsA1, $adminUser]), [
            'role' => WorkspaceRole::Member->value,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function org_admin_can_remove_member(): void
    {
        $workspaceUser = WorkspaceUser::factory()->create([
            'workspace_id' => $this->wsA1->id,
            'organization_id' => $this->orgA->id,
            'role' => WorkspaceRole::Member,
            'status' => WorkspaceUserStatus::Active,
        ]);

        Sanctum::actingAs($this->orgAdminA);

        $response = $this->deleteJson(route('api.organization.workspaces.members.destroy', [$this->orgA, $this->wsA1, $workspaceUser]));

        $response->assertOk()
            ->assertJsonPath('message', 'Member removed successfully');

        $this->assertSoftDeleted('workspace_users', ['id' => $workspaceUser->id]);
    }

    /** @test */
    public function org_admin_cannot_remove_self(): void
    {
        // Create workspace user for org admin
        $orgAdminWsUser = WorkspaceUser::factory()->create([
            'workspace_id' => $this->wsA1->id,
            'organization_id' => $this->orgA->id,
            'user_id' => $this->orgAdminA->id,
            'role' => WorkspaceRole::Admin,
            'status' => WorkspaceUserStatus::Active,
        ]);

        Sanctum::actingAs($this->orgAdminA);

        $response = $this->deleteJson(route('api.organization.workspaces.members.destroy', [$this->orgA, $this->wsA1, $orgAdminWsUser]));

        $response->assertForbidden();
    }

    /** @test */
    public function workspace_admin_cannot_remove_admin(): void
    {
        $adminUser = WorkspaceUser::factory()->create([
            'workspace_id' => $this->wsA1->id,
            'organization_id' => $this->orgA->id,
            'role' => WorkspaceRole::Admin,
            'status' => WorkspaceUserStatus::Active,
        ]);

        Sanctum::actingAs($this->workspaceAdminA);

        $response = $this->deleteJson(route('api.organization.workspaces.members.destroy', [$this->orgA, $this->wsA1, $adminUser]));

        $response->assertForbidden();
    }

    /** @test */
    public function org_admin_can_resend_invite(): void
    {
        $workspaceUser = WorkspaceUser::factory()->create([
            'workspace_id' => $this->wsA1->id,
            'organization_id' => $this->orgA->id,
            'role' => WorkspaceRole::Member,
            'status' => WorkspaceUserStatus::Pending,
        ]);

        Sanctum::actingAs($this->orgAdminA);

        $response = $this->postJson(route('api.organization.workspaces.members.resend-invite', [$this->orgA, $this->wsA1, $workspaceUser]));

        $response->assertOk()
            ->assertJsonPath('message', 'Invite resent successfully');
    }

    /** @test */
    public function cross_tenant_isolation_member_cannot_see_other_org_members(): void
    {
        $wsB1 = $this->orgB->workspaces()->create([
            'name' => 'Workspace B1',
            'slug' => 'ws-b1',
            'status' => 'active',
        ]);

        WorkspaceUser::factory()->create([
            'workspace_id' => $wsB1->id,
            'organization_id' => $this->orgB->id,
            'status' => WorkspaceUserStatus::Active,
        ]);

        Sanctum::actingAs($this->orgAdminA);

        $response = $this->getJson(route('api.organization.workspaces.members.index', [$this->orgB, $wsB1]));

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_be_member_of_multiple_workspaces_in_same_org(): void
    {
        $user = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'workspace_id' => $this->wsA1->id,
            'role' => UserRole::Member,
            'email_verified_at' => now(),
        ]);

        WorkspaceUser::factory()->create([
            'workspace_id' => $this->wsA1->id,
            'user_id' => $user->id,
            'organization_id' => $this->orgA->id,
            'role' => WorkspaceRole::Member,
            'status' => WorkspaceUserStatus::Active,
        ]);

        WorkspaceUser::factory()->create([
            'workspace_id' => $this->wsA2->id,
            'user_id' => $user->id,
            'organization_id' => $this->orgA->id,
            'role' => WorkspaceRole::Admin,
            'status' => WorkspaceUserStatus::Active,
        ]);

        $this->assertDatabaseCount('workspace_users', 2);
        
        // User can have different roles in different workspaces
        $memberships = WorkspaceUser::where('user_id', $user->id)->get();
        $this->assertEquals(WorkspaceRole::Member, $memberships->firstWhere('workspace_id', $this->wsA1->id)->role);
        $this->assertEquals(WorkspaceRole::Admin, $memberships->firstWhere('workspace_id', $this->wsA2->id)->role);
    }

    /** @test */
    public function invite_accept_flow_works(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->postJson(route('api.organization.workspaces.members.store', [$this->orgA, $this->wsA1]), [
            'email' => 'accept@test.com',
            'role' => WorkspaceRole::Member->value,
        ]);

        $response->assertCreated();
        $workspaceUserId = $response->json('data.id');

        // Accept invite via signed URL
        $url = URL::temporarySignedRoute('api.workspace.invite.accept', now()->addDays(7), ['workspace_user' => $workspaceUserId]);

        $acceptResponse = $this->get($url);
        
        $acceptResponse->assertOk()
            ->assertJsonPath('message', 'Convite aceito com sucesso!');

        $workspaceUser = WorkspaceUser::find($workspaceUserId);
        $this->assertEquals(WorkspaceUserStatus::Active, $workspaceUser->status);
        $this->assertNotNull($workspaceUser->joined_at);
    }

    /** @test */
    public function expired_invite_cannot_be_accepted(): void
    {
        Sanctum::actingAs($this->orgAdminA);

        $response = $this->postJson(route('api.organization.workspaces.members.store', [$this->orgA, $this->wsA1]), [
            'email' => 'expired@test.com',
            'role' => WorkspaceRole::Member->value,
        ]);

        $workspaceUserId = $response->json('data.id');

        // Try to accept with expired URL
        $url = URL::temporarySignedRoute('api.workspace.invite.accept', now()->subDay(), ['workspace_user' => $workspaceUserId]);

        $acceptResponse = $this->get($url);
        
        $acceptResponse->assertStatus(403); // Signature invalid/expired
    }
}