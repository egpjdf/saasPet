<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreOrganizationRequest;
use App\Http\Requests\Platform\UpdateOrganizationRequest;
use App\Http\Resources\Platform\OrganizationResource;
use App\Http\Resources\Platform\OrganizationCollection;
use App\Models\Organization;
use App\Services\Security\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Middleware('auth')]
#[Middleware('tenant.access')]
class OrganizationController extends Controller
{
    public function __construct(
        private AuditService $auditService,
    ) {}

    #[Authorize('viewAny', Organization::class)]
    public function index(Request $request): OrganizationCollection
    {
        $query = Organization::withoutOrganizationScope()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%");
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc');

        $organizations = $query->paginate(
            perPage: $request->integer('per_page', 15),
            page: $request->integer('page', 1),
        );

        return OrganizationCollection::make($organizations);
    }

    #[Authorize('create', Organization::class)]
    public function store(StoreOrganizationRequest $request): OrganizationResource
    {
        $organization = Organization::create($request->validated());

        $this->auditService->log(
            action: 'organization_created',
            resourceType: Organization::class,
            resourceId: (string) $organization->id,
            details: ['name' => $organization->name, 'slug' => $organization->slug],
            severity: 'info',
        );

        return OrganizationResource::make($organization->loadMissing([]));
    }

    #[Authorize('view', Organization::class)]
    public function show(Organization $organization): OrganizationResource
    {
        return OrganizationResource::make($organization->loadMissing([]));
    }

    #[Authorize('update', Organization::class)]
    public function update(UpdateOrganizationRequest $request, Organization $organization): OrganizationResource
    {
        $oldData = $organization->only(['name', 'slug', 'status', 'settings']);
        $organization->update($request->validated());

        $this->auditService->log(
            action: 'organization_updated',
            resourceType: Organization::class,
            resourceId: (string) $organization->id,
            details: ['old' => $oldData, 'new' => $organization->only(['name', 'slug', 'status', 'settings'])],
            severity: 'info',
        );

        return OrganizationResource::make($organization->loadMissing([]));
    }

    #[Authorize('delete', Organization::class)]
    public function destroy(Organization $organization): JsonResponse
    {
        $organization->delete();

        $this->auditService->log(
            action: 'organization_deleted',
            resourceType: Organization::class,
            resourceId: (string) $organization->id,
            details: ['name' => $organization->name, 'slug' => $organization->slug],
            severity: 'warning',
        );

        return response()->json(['message' => 'Organization deleted successfully']);
    }

    #[Authorize('restore', Organization::class)]
    public function restore(string $id): JsonResponse
    {
        $organization = Organization::withoutOrganizationScope()->withTrashed()->findOrFail($id);
        $organization->restore();

        $this->auditService->log(
            action: 'organization_restored',
            resourceType: Organization::class,
            resourceId: (string) $organization->id,
            details: ['name' => $organization->name, 'slug' => $organization->slug],
            severity: 'info',
        );

        return response()->json(['message' => 'Organization restored successfully']);
    }
}