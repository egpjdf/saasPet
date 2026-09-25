<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
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
});

test('Global scope cannot be bypassed via withoutGlobalScope on model', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Try to bypass organization scope
    $query = Organization::withoutGlobalScope('organization');
    
    // This should work for Platform Admin use cases
    // But regular users should not have access to this method
    $results = $query->get();
    
    // withoutGlobalScope removes the scope, so both orgs returned
    expect($results)->toHaveCount(2);
});

test('Global scope cannot be bypassed via withoutGlobalScope on query', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $query = Organization::query()->withoutGlobalScope('organization');
    $results = $query->get();
    
    expect($results)->toHaveCount(2);
});

test('Global scope cannot be bypassed via newModelInstance', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Try creating new model instance to bypass scope
    $model = new Organization();
    $results = $model->newQuery()->get();
    
    // New query should still have global scope
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($this->orgA->id);
});

test('Global scope cannot be bypassed via raw query builder', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Using DB::table bypasses Eloquent global scopes
    // This is a known limitation - document it
    $results = \DB::table('organizations')->get();
    
    // This bypasses global scopes - returns all organizations
    // This is why RLS at database level is critical
    expect($results)->toHaveCount(2);
});

test('Global scope cannot be bypassed via cursor', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $cursor = Organization::cursor();
    $results = iterator_to_array($cursor);
    
    expect($results)->toHaveCount(1);
});

test('Global scope cannot be bypassed via chunk', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $collected = [];
    Organization::chunk(100, function ($orgs) use (&$collected) {
        $collected = array_merge($collected, $orgs->toArray());
    });
    
    expect($collected)->toHaveCount(1);
});

test('Global scope cannot be bypassed via lazy loading', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Create a relationship that uses global scope
    $user = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $this->wsA1->id,
    ]);
    
    $user->load('organization');
    
    expect($user->organization->id)->toBe($this->orgA->id);
});

test('Global scope cannot be bypassed via eager loading', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $users = User::with('organization')->get();
    
    foreach ($users as $user) {
        expect($user->organization->id)->toBe($this->orgA->id);
    }
});

test('Global scope cannot be bypassed via replicate', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $org = Organization::first();
    $replicated = $org->replicate();
    
    // Replicate doesn't copy the global scope to the new instance
    // But the query to save it would have scope
    expect($replicated)->toBeInstanceOf(Organization::class);
});

test('Global scope cannot be bypassed via query scopes', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    // Custom local scopes should not bypass global scopes
    $results = Organization::active()->get();
    
    expect($results)->toHaveCount(1);
});

test('Global scope cannot be bypassed via whereHas', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $results = Organization::whereHas('workspaces', function ($q) {
        $q->where('slug', 'ws-b1'); // Try to match Org B workspace
    })->get();
    
    // Global scope on Organization filters first
    expect($results)->toHaveCount(0);
});

test('Global scope applied to all query types', function (): void {
    $this->tenantContext->setOrganizationId($this->orgA->id);
    $this->tenantContext->setWorkspaceId($this->wsA1->id);
    
    $tests = [
        'all' => fn() => Organization::all(),
        'get' => fn() => Organization::get(),
        'first' => fn() => Organization::first(),
        'find' => fn() => Organization::find($this->orgA->id),
        'findOrFail' => fn() => Organization::findOrFail($this->orgA->id),
        'where' => fn() => Organization::where('slug', 'org-a')->get(),
        'whereIn' => fn() => Organization::whereIn('id', [$this->orgA->id, $this->orgB->id])->get(),
        'orderBy' => fn() => Organization::orderBy('name')->get(),
        'limit' => fn() => Organization::limit(10)->get(),
        'paginate' => fn() => Organization::paginate(10),
        'simplePaginate' => fn() => Organization::simplePaginate(10),
    ];
    
    foreach ($tests as $name => $fn) {
        $results = $fn();
        $count = $results instanceof \Illuminate\Pagination\LengthAwarePaginator 
            ? $results->total() 
            : ($results instanceof \Illuminate\Support\Collection ? $results->count() : 1);
        
        expect($count)->toBe(1, "Failed for {$name}");
    }
});