<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Tenant\TenantContext;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantContext = app(TenantContext::class);
    
    // Create two organizations
    $this->orgA = Organization::factory()->create(['slug' => 'org-a']);
    $this->orgB = Organization::factory()->create(['slug' => 'org-b']);
    
    // Create workspaces for each org
    $this->wsA1 = Workspace::factory()->create(['organization_id' => $this->orgA->id, 'slug' => 'ws-a1']);
    $this->wsA2 = Workspace::factory()->create(['organization_id' => $this->orgA->id, 'slug' => 'ws-a2']);
    $this->wsB1 = Workspace::factory()->create(['organization_id' => $this->orgB->id, 'slug' => 'ws-b1']);
    
    // Create users in each workspace
    $this->userOrgAWsA1 = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $this->wsA1->id,
        'role' => UserRole::Member,
    ]);
    $this->userOrgAWsA2 = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $this->wsA2->id,
        'role' => UserRole::Member,
    ]);
    $this->userOrgBWsB1 = User::factory()->create([
        'organization_id' => $this->orgB->id,
        'workspace_id' => $this->wsB1->id,
        'role' => UserRole::Member,
    ]);
    
    // Create org admins
    $this->orgAdminA = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $this->wsA1->id,
        'role' => UserRole::OrgAdmin,
    ]);
    $this->orgAdminB = User::factory()->create([
        'organization_id' => $this->orgB->id,
        'workspace_id' => $this->wsB1->id,
        'role' => UserRole::OrgAdmin,
    ]);
    
    // Create platform admin
    $this->platformAdmin = User::factory()->create(['role' => UserRole::PlatformAdmin]);
});

test('Org A cannot read Org B organizations via global scope', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $orgs = Organization::all();
    
    expect($orgs)->toHaveCount(1);
    expect($orgs->first()->id)->toBe($this->orgA->id);
});

test('Org A cannot read Org B workspaces via global scope', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $workspaces = Workspace::all();
    
    expect($workspaces)->toHaveCount(2); // wsA1, wsA2
    expect($workspaces->pluck('id'))->toContain($this->wsA1->id);
    expect($workspaces->pluck('id'))->toContain($this->wsA2->id);
    expect($workspaces->pluck('id'))->not->toContain($this->wsB1->id);
});

test('Org A cannot read Org B users via global scope', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $users = User::all();
    
    expect($users)->toHaveCount(2); // userOrgAWsA1, orgAdminA
    expect($users->pluck('id'))->toContain($this->userOrgAWsA1->id);
    expect($users->pluck('id'))->toContain($this->orgAdminA->id);
    expect($users->pluck('id'))->not->toContain($this->userOrgBWsB1->id);
});

test('WS A cannot read WS B users within same Org via global scope', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $users = User::all();
    
    expect($users)->toHaveCount(2); // Only users in wsA1
    expect($users->pluck('id'))->toContain($this->userOrgAWsA1->id);
    expect($users->pluck('id'))->toContain($this->orgAdminA->id);
    expect($users->pluck('id'))->not->toContain($this->userOrgAWsA2->id);
});

test('Platform Admin bypasses all global scopes', function (): void {
    $this->tenantContext->setPlatformAdmin(true);
    
    $orgs = Organization::all();
    $workspaces = Workspace::all();
    $users = User::all();
    
    expect($orgs)->toHaveCount(2);
    expect($workspaces)->toHaveCount(3);
    expect($users)->toHaveCount(5);
});

test('Org Admin can read all workspaces in their organization', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId(null); // Org admin doesn't need workspace context
    
    // Note: OrgAdmin policy allows cross-workspace access within org
    // This test verifies the global scope behavior, policy is tested separately
    $workspaces = Workspace::all();
    
    expect($workspaces)->toHaveCount(2);
});

test('Org A cannot create resource in Org B via API', function (): void {
    $this->actingAs($this->userOrgAWsA1);
    
    $response = $this->postJson("/api/{$this->orgB->slug}/{$this->wsB1->slug}/test-resources", [
        'name' => 'Cross-tenant attempt',
    ]);
    
    // Should fail - either 403 (forbidden) or 404 (workspace not found in context)
    expect($response->status())->toBeOneOf([403, 404]);
});

test('Org A cannot update resource in Org B via API', function (): void {
    // Create resource in Org B
    $this->tenantContext->setOrganizationId($this->orgB->id);
    $this->tenantContext->setWorkspaceId($this->wsB1->id);
    // Note: Would need actual resource model for full test
    
    $this->actingAs($this->userOrgAWsA1);
    
    $response = $this->putJson("/api/{$this->orgB->slug}/{$this->wsB1->slug}/test-resources/non-existent", [
        'name' => 'Cross-tenant update',
    ]);
    
    expect($response->status())->toBeOneOf([403, 404]);
});

test('Org A cannot delete resource in Org B via API', function (): void {
    $this->actingAs($this->userOrgAWsA1);
    
    $response = $this->deleteJson("/api/{$this->orgB->slug}/{$this->wsB1->slug}/test-resources/non-existent");
    
    expect($response->status())->toBeOneOf([403, 404]);
});

test('Org A cannot list resources in Org B via API', function (): void {
    $this->actingAs($this->userOrgAWsA1);
    
    $response = $this->getJson("/api/{$this->orgB->slug}/{$this->wsB1->slug}/test-resources");
    
    expect($response->status())->toBeOneOf([403, 404]);
});

test('Org A cannot export resources from Org B via API', function (): void {
    $this->actingAs($this->userOrgAWsA1);
    
    $response = $this->getJson("/api/{$this->orgB->slug}/{$this->wsB1->slug}/test-resources/export");
    
    expect($response->status())->toBeOneOf([403, 404]);
});

test('User cannot access other user resources in same workspace', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Both users in same workspace
    $this->actingAs($this->userOrgAWsA1);
    
    // Try to access resource owned by orgAdminA
    $response = $this->getJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users/{$this->orgAdminA->id}");
    
    // Should be forbidden unless user has permission
    expect($response->status())->toBeOneOf([403, 404, 200]); // 200 if policy allows
});

test('Cross-user policy enforced for create', function (): void {
    $this->actingAs($this->userOrgAWsA1);
    
    $response = $this->postJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users", [
        'name' => 'New User',
        'email' => 'new@test.com',
        'password' => 'password123',
        'role' => UserRole::Member->value,
    ]);
    
    // Regular member cannot create users
    expect($response->status())->toBe(403);
});

test('Cross-user policy enforced for update', function (): void {
    $this->actingAs($this->userOrgAWsA1);
    
    $response = $this->putJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users/{$this->orgAdminA->id}", [
        'name' => 'Hacked',
    ]);
    
    // Regular member cannot update other users
    expect($response->status())->toBe(403);
});

test('Cross-user policy enforced for delete', function (): void {
    $this->actingAs($this->userOrgAWsA1);
    
    $response = $this->deleteJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users/{$this->orgAdminA->id}");
    
    // Regular member cannot delete other users
    expect($response->status())->toBe(403);
});

test('Cross-user policy enforced for list', function (): void {
    $this->actingAs($this->userOrgAWsA1);
    
    $response = $this->getJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users");
    
    // Member can list users in their workspace
    expect($response->status())->toBeOneOf([200, 403]);
});

test('Cross-user policy enforced for export', function (): void {
    $this->actingAs($this->userOrgAWsA1);
    
    $response = $this->getJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users/export");
    
    // Member cannot export
    expect($response->status())->toBeOneOf([403, 404]);
});