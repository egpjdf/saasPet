<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreWorkspaceRequest;
use App\Http\Requests\Organization\UpdateWorkspaceRequest;
use App\Http\Resources\Organization\WorkspaceResource;
use App\Http\Resources\Organization\WorkspaceCollection;
use App\Models\Organization;
use App\Models\Workspace;
use App\Services\Security\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Middleware('auth')]
#[Middleware('tenant.auth')]
class WorkspaceController extends Controller
{
    public function __construct(
        private AuditService $auditService,
    ) {}

    #[Authorize('viewAny', Workspace::class)]
    public function index(Request $request, Organization $organization): WorkspaceCollection
    {
        $query = $organization->workspaces()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%");
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc');

        $workspaces = $query->paginate(
            perPage: $request->integer('per_page', 15),
            page: $request->integer('page', 1),
        );

        return WorkspaceCollection::make($workspaces);
    }

    #[Authorize('create', Workspace::class)]
    public function store(StoreWorkspaceRequest $request, Organization $organization): WorkspaceResource
    {
        $workspace = $organization->workspaces()->create($request->validated());

        $this->auditService->log(
            action: 'workspace_created',
            resourceType: Workspace::class,
            resourceId: (string) $workspace->id,
            details: ['name' => $workspace->name, 'slug' => $workspace->slug, 'organization_id' => (string) $organization->id],
            severity: 'info',
        );

        return WorkspaceResource::make($workspace->loadMissing(['organization']));
    }

    #[Authorize('view', Workspace::class)]
    public function show(Organization $organization, Workspace $workspace): WorkspaceResource
    {
        return WorkspaceResource::make($workspace->loadMissing(['organization']));
    }

    #[Authorize('update', Workspace::class)]
    public function update(UpdateWorkspaceRequest $request, Organization $organization, Workspace $workspace): WorkspaceResource
    {
        $oldData = $workspace->only(['name', 'slug', 'status', 'settings']);
        $workspace->update($request->validated());

        $this->auditService->log(
            action: 'workspace_updated',
            resourceType: Workspace::class,
            resourceId: (string) $workspace->id,
            details: ['old' => $oldData, 'new' => $workspace->only(['name', 'slug', 'status', 'settings'])],
            severity: 'info',
        );

        return WorkspaceResource::make($workspace->loadMissing(['organization']));
    }

    #[Authorize('delete', Workspace::class)]
    public function destroy(Organization $organization, Workspace $workspace): JsonResponse
    {
        $workspace->delete();

        $this->auditService->log(
            action: 'workspace_deleted',
            resourceType: Workspace::class,
            resourceId: (string) $workspace->id,
            details: ['name' => $workspace->name, 'slug' => $workspace->slug],
            severity: 'warning',
        );

        return response()->json(['message' => 'Workspace deleted successfully']);
    }

    #[Authorize('restore', Workspace::class)]
    public function restore(Organization $organization, string $id): JsonResponse
    {
        $workspace = $organization->workspaces()->withTrashed()->findOrFail($id);
        $workspace->restore();

        $this->auditService->log(
            action: 'workspace_restored',
            resourceType: Workspace::class,
            resourceId: (string) $workspace->id,
            details: ['name' => $workspace->name, 'slug' => $workspace->slug],
            severity: 'info',
        );

        return response()->json(['message' => 'Workspace restored successfully']);
    }
}