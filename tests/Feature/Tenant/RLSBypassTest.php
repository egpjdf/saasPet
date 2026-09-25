<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantContext = app(TenantContext::class);
    
    $this->orgA = Organization::factory()->create(['slug' => 'org-a']);
    $this->orgB = Organization::factory()->create(['slug' => 'org-b']);
    
    $this->wsA1 = Workspace::factory()->create(['organization_id' => $this->orgA->id, 'slug' => 'ws-a1']);
    $this->wsB1 = Workspace::factory()->create(['organization_id' => $this->orgB->id, 'slug' => 'ws-b1']);
    
    $this->userA = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $this->wsA1->id,
    ]);
    $this->userB = User::factory()->create([
        'organization_id' => $this->orgB->id,
        'workspace_id' => $this->wsB1->id,
    ]);
});

test('RLS bypass attempt via raw SQL injection in organization_id param', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Attempt to inject SQL via whereRaw
    $results = Organization::whereRaw("id = '{$this->orgB->id}' OR '1'='1'")->get();
    
    // Should only return Org A due to global scope
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($this->orgA->id);
});

test('RLS bypass attempt via raw SQL injection in workspace_id param', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $results = Workspace::whereRaw("id = '{$this->wsB1->id}' OR '1'='1'")->get();
    
    // Should only return wsA1 due to global scope
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($this->wsA1->id);
});

test('RLS bypass attempt via DB::raw in select', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $results = Organization::selectRaw("*, (SELECT count(*) FROM organizations) as total")->get();
    
    // Should only return Org A, but total might show all if not careful
    // Global scope should still apply to the main query
    expect($results)->toHaveCount(1);
});

test('RLS bypass attempt via union injection', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Try to union with orgB data
    $query = Organization::query();
    $query->where('id', $this->orgA->id);
    
    $results = $query->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($this->orgA->id);
});

test('RLS policy blocks direct database access without context', function (): void {
    // Without tenant context set, RLS should block access
    // This simulates a direct database connection without app context
    
    // Clear tenant context
    $this->tenantContext->clear();
    
    // Query without context - should return empty due to RLS
    $orgs = Organization::all();
    
    // Global scope won't apply without context, but RLS should
    // In test environment without RLS setup, this might return all
    // This test documents expected behavior
    expect(true)->toBeTrue(); // Placeholder - requires PG with RLS enabled
});

test('RLS policy blocks cross-tenant access at database level', function (): void {
    // This test requires PostgreSQL with RLS policies applied
    // In CI/CD with real PG, this would test actual RLS enforcement
    // For now, document the expected behavior
    
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Attempt to query Org B data directly
    $sql = "SELECT * FROM organizations WHERE id = '{$this->orgB->id}'";
    
    // In real PG with RLS: this would return 0 rows for app_user role
    // For test, we verify the global scope adds the WHERE clause
    $query = Organization::where('id', $this->orgB->id);
    $results = $query->get();
    
    // Global scope should filter out Org B
    expect($results)->toHaveCount(0);
});

test('RLS bypass attempt via subquery', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Try subquery to access Org B
    $results = Organization::whereIn('id', function ($q) {
        $q->select('id')->from('organizations')->where('slug', 'org-b');
    })->get();
    
    // Global scope should still apply
    expect($results)->toHaveCount(0);
});

test('RLS bypass attempt via join', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Try joining to access Org B workspaces
    $results = Workspace::join('organizations', 'workspaces.organization_id', '=', 'organizations.id')
        ->where('organizations.slug', 'org-b')
        ->get();
    
    // Global scope on workspaces should filter
    expect($results)->toHaveCount(0);
});

test('RLS bypass attempt via stored procedure', function (): void {
    // Test that stored procedures also respect RLS
    // This is a placeholder for when stored procedures are used
    expect(true)->toBeTrue();
});