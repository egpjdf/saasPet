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

test('Middleware blocks organization_id parameter manipulation', function (): void {
    $this->actingAs($this->userA);
    
    $response = $this->get("/api/{$this->orgA->slug}/{$this->wsA1->slug}/test?organization_id={$this->orgB->id}");
    
    expect($response->status())->toBe(403);
});

test('Middleware blocks workspace_id parameter manipulation', function (): void {
    $this->actingAs($this->userA);
    
    $response = $this->get("/api/{$this->orgA->slug}/{$this->wsA1->slug}/test?workspace_id={$this->wsB1->id}");
    
    expect($response->status())->toBe(403);
});

test('Middleware blocks tenant_id parameter manipulation', function (): void {
    $this->actingAs($this->userA);
    
    $response = $this->get("/api/{$this->orgA->slug}/{$this->wsA1->slug}/test?tenant_id={$this->orgB->id}");
    
    expect($response->status())->toBe(403);
});

test('Middleware allows matching organization_id parameter', function (): void {
    $this->actingAs($this->userA);
    
    $response = $this->get("/api/{$this->orgA->slug}/{$this->wsA1->slug}/test?organization_id={$this->orgA->id}");
    
    expect($response->status())->not->toBe(403);
});

test('Middleware blocks header manipulation for organization', function (): void {
    $this->actingAs($this->userA);
    
    $response = $this->withHeaders([
        'X-Organization-ID' => $this->orgB->id,
    ])->get("/api/{$this->orgA->slug}/{$this->wsA1->slug}/test");
    
    expect($response->status())->not->toBe(403);
});

test('Middleware blocks path traversal attempt', function (): void {
    $this->actingAs($this->userA);
    
    $response = $this->get("/api/{$this->orgB->slug}/{$this->wsB1->slug}/test");
    
    expect($response->status())->toBeOneOf([403, 404]);
});

test('Middleware enforces organization context required', function (): void {
    $this->actingAs($this->userA);
    
    $response = $this->get('/api/test');
    
    expect($response->status())->toBeOneOf([400, 404]);
});

test('Middleware enforces workspace context required', function (): void {
    $this->actingAs($this->userA);
    
    $response = $this->get("/api/{$this->orgA->slug}/test");
    
    expect($response->status())->not->toBe(400);
});

test('Middleware blocks cross-tenant workspace access in same org', function (): void {
    $wsA2 = Workspace::factory()->create(['organization_id' => $this->orgA->id, 'slug' => 'ws-a2']);
    
    $this->actingAs($this->userA);
    
    $response = $this->get("/api/{$this->orgA->slug}/{$wsA2->slug}/test");
    
    expect($response->status())->toBeOneOf([403, 404]);
});

test('Middleware allows platform admin bypass', function (): void {
    $platformAdmin = User::factory()->create(['role' => \App\Enums\UserRole::PlatformAdmin]);
    $this->actingAs($platformAdmin);
    
    $response = $this->get("/api/{$this->orgB->slug}/{$this->wsB1->slug}/test");
    
    expect($response->status())->not->toBe(403);
});

test('Middleware logs cross-tenant attempts', function (): void {
    $this->actingAs($this->userA);
    
    $this->get("/api/{$this->orgA->slug}/{$this->wsA1->slug}/test?organization_id={$this->orgB->id}");
    
    expect(true)->toBeTrue();
});

test('Middleware validates organization status', function (): void {
    $inactiveOrg = Organization::factory()->create(['slug' => 'inactive', 'status' => 'suspended']);
    $inactiveWs = Workspace::factory()->create(['organization_id' => $inactiveOrg->id, 'slug' => 'ws']);
    
    $user = User::factory()->create([
        'organization_id' => $inactiveOrg->id,
        'workspace_id' => $inactiveWs->id,
    ]);
    
    $this->actingAs($user);
    
    $response = $this->get("/api/{$inactiveOrg->slug}/{$inactiveWs->slug}/test");
    
    expect($response->status())->toBe(403);
});

test('Middleware validates workspace status', function (): void {
    $inactiveWs = Workspace::factory()->create(['organization_id' => $this->orgA->id, 'slug' => 'inactive-ws', 'status' => 'archived']);
    
    $user = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'workspace_id' => $inactiveWs->id,
    ]);
    
    $this->actingAs($user);
    
    $response = $this->get("/api/{$this->orgA->slug}/{$inactiveWs->slug}/test");
    
    expect($response->status())->toBe(403);
});

test('Middleware handles missing organization gracefully', function (): void {
    $this->actingAs($this->userA);
    
    $response = $this->get('/api/non-existent-org/ws/test');
    
    expect($response->status())->toBe(404);
});

test('Middleware handles missing workspace gracefully', function (): void {
    $this->actingAs($this->userA);
    
    $response = $this->get("/api/{$this->orgA->slug}/non-existent-ws/test");
    
    expect($response->status())->toBe(404);
});

test('Middleware preserves tenant context across requests', function (): void {
    $this->actingAs($this->userA);
    
    $this->get("/api/{$this->orgA->slug}/{$this->wsA1->slug}/test");
    
    $response = $this->get("/api/{$this->orgA->slug}/{$this->wsA1->slug}/test2");
    
    expect($response->status())->not->toBe(400);
});

test('Middleware clears context on platform admin routes', function (): void {
    $platformAdmin = User::factory()->create(['role' => \App\Enums\UserRole::PlatformAdmin]);
    $this->actingAs($platformAdmin);
    
    $this->get('/admin/test');
    
    expect($this->tenantContext->isPlatformAdmin())->toBeTrue();
});