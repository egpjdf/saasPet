<?php

declare(strict_types=1);

namespace App\Http\Resources\Organization;

use App\Models\WorkspaceUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var WorkspaceUser $this */
        return [
            'id' => (string) $this->id,
            'type' => 'workspace_members',
            'attributes' => [
                'role' => $this->role?->value,
                'role_label' => $this->role?->label(),
                'status' => $this->status?->value,
                'status_label' => $this->status?->label(),
                'permissions' => $this->role?->permissions() ?? [],
                'invited_at' => $this->invited_at?->toISOString(),
                'joined_at' => $this->joined_at?->toISOString(),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
            'relationships' => [
                'user' => $this->whenLoaded('user', function () {
                    return [
                        'data' => [
                            'id' => (string) $this->user->id,
                            'type' => 'users',
                            'attributes' => [
                                'name' => $this->user->name,
                                'email' => $this->user->email,
                                'avatar_path' => $this->user->avatar_path,
                                'role' => $this->user->role?->value,
                            ],
                        ],
                    ];
                }),
                'inviter' => $this->whenLoaded('inviter', function () {
                    return [
                        'data' => [
                            'id' => (string) $this->inviter->id,
                            'type' => 'users',
                            'attributes' => [
                                'name' => $this->inviter->name,
                                'email' => $this->inviter->email,
                            ],
                        ],
                    ];
                }),
            ],
            'links' => [
                'self' => route('api.organizations.workspaces.members.show', [$this->organization, $this->workspace, $this]),
            ],
        ];
    }
}