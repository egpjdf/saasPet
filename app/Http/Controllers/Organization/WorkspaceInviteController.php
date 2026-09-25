<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceUser;
use App\Services\Organization\WorkspaceMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class WorkspaceInviteController extends Controller
{
    public function __construct(
        private WorkspaceMemberService $memberService,
    ) {}

    public function accept(WorkspaceUser $workspaceUser): JsonResponse
    {
        try {
            $user = $this->memberService->accept((string) $workspaceUser->id);

            return response()->json([
                'message' => 'Convite aceito com sucesso!',
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'workspace' => [
                    'id' => (string) $workspaceUser->workspace_id,
                    'name' => $workspaceUser->workspace->name,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Convite inválido ou expirado.',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}