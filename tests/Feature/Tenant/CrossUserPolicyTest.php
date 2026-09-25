<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\UserRole;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantContext = app(TenantContext::class);
    
    $this->orgA = Organization::factory()->create(['slug' => 'org-a']);
    $this->orgB = Organization::factory()->create(['slug' => 'org-b']);
    
    $this->wsA1 = Workspace::factory()->create(['organization_id' => $this->orgA->id, 'slug' => 'ws-a1']);
    $this->wsA2 = Workspace::factory()->create(['organization_id' => $this->orgA->id, 'slug' => 'ws-a2']);
    $this->wsB1 = Workspace::factory()->create(['organization_id' => $this->orgB->id, 'slug' => 'ws-b1']);
    
    // Regular members
    $this->user1 = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $this->wsA1->id,
        'role' => UserRole::Member,
    ]);
    $this->user2 = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $this->wsA1->id,
        'role' => UserRole::Member,
    ]);
    $this->user3 = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $this->wsA2->id,
        'role' => UserRole::Member,
    ]);
    $this->userB = User::factory()->create([
        'organization_id' => $this->orgB->id,
        'workspace_id' => $this->wsB1->id,
        'role' => UserRole::Member,
    ]);
    
    // Org admin
    $this->orgAdmin = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $this->wsA1->id,
        'role' => UserRole::OrgAdmin,
    ]);
    
    // Platform admin
    $this->platformAdmin = User::factory()->create(['role' => UserRole::PlatformAdmin]);
});

test('Member cannot view other member in same workspace', function (): void {
    $this->actingAs($this->user1);
    
    $this->assertFalse($this->user1->can('view', $this->user2));
});

test('Member cannot update other member in same workspace', function (): void {
    $this->actingAs($this->user1);
    
    $this->assertFalse($this->user1->can('update', $this->user2));
});

test('Member cannot delete other member in same workspace', function (): void {
    $this->actingAs($this->user1);
    
    $this->assertFalse($this->user1->can('delete', $this->user2));
});

test('Member can view self', function (): void {
    $this->actingAs($this->user1);
    
    $this->assertTrue($this->user1->can('view', $this->user1));
});

test('Member can update self', function (): void {
    $this->actingAs($this->user1);
    
    $this->assertTrue($this->user1->can('update', $this->user1));
});

test('Member cannot delete self', function (): void {
    $this->actingAs($this->user1);
    
    $this->assertFalse($this->user1->can('delete', $this->user1));
});

test('Member cannot view member in different workspace same org', function (): void {
    $this->actingAs($this->user1);
    
    $this->assertFalse($this->user1->can('view', $this->user3));
});

test('Member cannot view member in different org', function (): void {
    $this->actingAs($this->user1);
    
    $this->assertFalse($this->user1->can('view', $this->userB));
});

test('Org Admin can view all members in their organization', function (): void {
    $this->actingAs($this->orgAdmin);
    
    $this->assertTrue($this->orgAdmin->can('view', $this->user1));
    $this->assertTrue($this->orgAdmin->can('view', $this->user2));
    $this->assertTrue($this->orgAdmin->can('view', $this->user3));
});

test('Org Admin can update all members in their organization', function (): void {
    $this->actingAs($this->orgAdmin);
    
    $this->assertTrue($this->orgAdmin->can('update', $this->user1));
    $this->assertTrue($this->orgAdmin->can('update', $this->user2));
    $this->assertTrue($this->orgAdmin->can('update', $this->user3));
});

test('Org Admin can delete members in their organization', function (): void {
    $this->actingAs($this->orgAdmin);
    
    $this->assertTrue($this->orgAdmin->can('delete', $this->user1));
    $this->assertTrue($this->orgAdmin->can('delete', $this->user2));
    $this->assertTrue($this->orgAdmin->can('delete', $this->user3));
});

test('Org Admin cannot view members in other organization', function (): void {
    $this->actingAs($this->orgAdmin);
    
    $this->assertFalse($this->orgAdmin->can('view', $this->userB));
});

test('Org Admin cannot update members in other organization', function (): void {
    $this->actingAs($this->orgAdmin);
    
    $this->assertFalse($this->orgAdmin->can('update', $this->userB));
});

test('Org Admin cannot delete members in other organization', function (): void {
    $this->actingAs($this->orgAdmin);
    
    $this->assertFalse($this->orgAdmin->can('delete', $this->userB));
});

test('Platform Admin can view all users', function (): void {
    $this->actingAs($this->platformAdmin);
    
    $this->assertTrue($this->platformAdmin->can('view', $this->user1));
    $this->assertTrue($this->platformAdmin->can('view', $this->user2));
    $this->assertTrue($this->platformAdmin->can('view', $this->user3));
    $this->assertTrue($this->platformAdmin->can('view', $this->userB));
});

test('Platform Admin can update all users', function (): void {
    $this->actingAs($this->platformAdmin);
    
    $this->assertTrue($this->platformAdmin->can('update', $this->user1));
    $this->assertTrue($this->platformAdmin->can('update', $this->userB));
});

test('Platform Admin can delete all users', function (): void {
    $this->actingAs($this->platformAdmin);
    
    $this->assertTrue($this->platformAdmin->can('delete', $this->user1));
    $this->assertTrue($this->platformAdmin->can('delete', $this->userB));
});

test('Member cannot list users in workspace via policy', function (): void {
    $this->actingAs($this->user1);
    
    // viewAny on User model
    $this->assertFalse($this->user1->can('viewAny', User::class));
});

test('Org Admin can list users in organization via policy', function (): void {
    $this->actingAs($this->orgAdmin);
    
    $this->assertTrue($this->orgAdmin->can('viewAny', User::class));
});

test('Platform Admin can list all users via policy', function (): void {
    $this->actingAs($this->platformAdmin);
    
    $this->assertTrue($this->platformAdmin->can('viewAny', User::class));
});

test('Cross-user API access blocked for member', function (): void {
    $this->actingAs($this->user1);
    
    $response = $this->getJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users/{$this->user2->id}");
    
    expect($response->status())->toBe(403);
});

test('Cross-user API access blocked for member update', function (): void {
    $this->actingAs($this->user1);
    
    $response = $this->putJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users/{$this->user2->id}", [
        'name' => 'Hacked',
    ]);
    
    expect($response->status())->toBe(403);
});

test('Cross-user API access blocked for member delete', function (): void {
    $this->actingAs($this->user1);
    
    $response = $this->deleteJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users/{$this->user2->id}");
    
    expect($response->status())->toBe(403);
});

test('Cross-user API access blocked for member list', function (): void {
    $this->actingAs($this->user1);
    
    $response = $this->getJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users");
    
    // Member cannot list users
    expect($response->status())->toBe(403);
});

test('Cross-user API access blocked for member export', function (): void {
    $this->actingAs($this->user1);
    
    $response = $this->getJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users/export");
    
    expect($response->status())->toBe(403);
});

test('Org Admin API access allowed for users in org', function (): void {
    $this->actingAs($this->orgAdmin);
    
    $response = $this->getJson("/api/{$this->orgA->slug}/{$this->wsA1->slug}/users/{$this->user1->id}");
    
    expect($response->status())->toBe(200);
});

test('Org Admin API access allowed for cross-workspace users in org', function (): void {
    $this->actingAs($this->orgAdmin);
    
    $response = $this->getJson("/api/{$this->orgA->slug}/{$this->wsA2->slug}/users/{$this->user3->id}");
    
    expect($response->status())->toBe(200);
});

test('Org Admin API access blocked for other org', function (): void {
    $this->actingAs($this->orgAdmin);
    
    $response = $this->getJson("/api/{$this->orgB->slug}/{$this->wsB1->slug}/users/{$this->userB->id}");
    
    expect($response->status())->toBeOneOf([403, 404]);
});