<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\InviteMemberRequest;
use App\Http\Requests\Organization\UpdateMemberRoleRequest;
use App\Http\Resources\Organization\WorkspaceMemberResource;
use App\Http\Resources\Organization\WorkspaceMemberCollection;
use App\Models\Organization;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\Organization\WorkspaceMemberService;
use Illuminate\Http\JsonResponse;

#[Middleware('auth')]
#[Middleware('tenant.auth')]
class WorkspaceMemberController extends Controller
{
    public function __construct(
        private WorkspaceMemberService $memberService,
    ) {}

    public function index(Organization $organization, Workspace $workspace): WorkspaceMemberCollection
    {
        $members = $workspace->members()
            ->with(['user', 'inviter'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return WorkspaceMemberCollection::make($members);
    }

    public function store(InviteMemberRequest $request, Organization $organization, Workspace $workspace): WorkspaceMemberResource
    {
        $workspaceUser = $this->memberService->invite(
            $workspace,
            $request->validated('email'),
            $request->validated('role'),
            $request->user()
        );

        return WorkspaceMemberResource::make($workspaceUser->loadMissing(['user', 'inviter']));
    }

    public function update(UpdateMemberRoleRequest $request, Organization $organization, Workspace $workspace, WorkspaceUser $workspaceUser): WorkspaceMemberResource
    {
        $this->memberService->updateRole($workspaceUser, $request->validated('role'));

        return WorkspaceMemberResource::make($workspaceUser->loadMissing(['user', 'inviter']));
    }

    public function destroy(Organization $organization, Workspace $workspace, WorkspaceUser $workspaceUser): JsonResponse
    {
        $this->memberService->remove($workspaceUser);

        return response()->json(['message' => 'Member removed successfully']);
    }

    public function resendInvite(Organization $organization, Workspace $workspace, WorkspaceUser $workspaceUser): JsonResponse
    {
        $this->memberService->resendInvite($workspaceUser);

        return response()->json(['message' => 'Invite resent successfully']);
    }
}