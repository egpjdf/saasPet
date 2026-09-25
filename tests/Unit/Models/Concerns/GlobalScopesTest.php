<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantContext = app(TenantContext::class);
});

test('BelongsToOrganization trait applies global scope when organizationId is set', function (): void {
    $orgA = Organization::factory()->create(['slug' => 'org-a']);
    $orgB = Organization::factory()->create(['slug' => 'org-b']);

    $this->tenantContext->setOrganizationId($orgA->id);

    $query = Organization::query();
    $sql = $query->toSql();

    expect($sql)->toContain('organization_id');
});

test('BelongsToOrganization trait does not apply scope for Platform Admin', function (): void {
    $orgA = Organization::factory()->create(['slug' => 'org-a']);

    $this->tenantContext->setPlatformAdmin(true);

    $query = Organization::query();
    $sql = $query->toSql();

    expect($sql)->not->toContain('organization_id');
});

test('BelongsToWorkspace trait applies global scope when workspaceId is set', function (): void {
    $org = Organization::factory()->create();
    $wsA = Workspace::factory()->create(['organization_id' => $org->id, 'slug' => 'ws-a']);
    $wsB = Workspace::factory()->create(['organization_id' => $org->id, 'slug' => 'ws-b']);

    $this->tenantContext->setOrganizationId($org->id);
    $this->tenantContext->setWorkspaceId($wsA->id);

    $query = Workspace::query();
    $sql = $query->toSql();

    expect($sql)->toContain('workspace_id');
});

test('withoutOrganizationScope removes the global scope', function (): void {
    $orgA = Organization::factory()->create(['slug' => 'org-a']);
    $orgB = Organization::factory()->create(['slug' => 'org-b']);

    $this->tenantContext->setOrganizationId($orgA->id);

    $query = Organization::withoutOrganizationScope();
    $sql = $query->toSql();

    expect($sql)->not->toContain('organization_id');
});

test('withoutWorkspaceScope removes the global scope', function (): void {
    $org = Organization::factory()->create();
    $wsA = Workspace::factory()->create(['organization_id' => $org->id, 'slug' => 'ws-a']);

    $this->tenantContext->setOrganizationId($org->id);
    $this->tenantContext->setWorkspaceId($wsA->id);

    $query = Workspace::withoutWorkspaceScope();
    $sql = $query->toSql();

    expect($sql)->not->toContain('workspace_id');
});