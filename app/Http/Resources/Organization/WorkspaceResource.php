<?php

declare(strict_types=1);

namespace App\Http\Resources\Organization;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Workspace $this */
        return [
            'id' => (string) $this->id,
            'type' => 'workspaces',
            'attributes' => [
                'name' => $this->name,
                'slug' => $this->slug,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'status' => $this->status?->value,
                'settings' => $this->settings,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'deleted_at' => $this->deleted_at?->toISOString(),
            ],
            'relationships' => [
                'organization' => [
                    'data' => [
                        'id' => (string) $this->organization_id,
                        'type' => 'organizations',
                    ],
                    'links' => [
                        'related' => route('api.organizations.show', $this->organization_id),
                    ],
                ],
                'members' => [
                    'links' => [
                        'related' => route('api.organizations.workspaces.members.index', [$this->organization, $this]),
                    ],
                ],
            ],
            'links' => [
                'self' => route('api.organizations.workspaces.show', [$this->organization, $this]),
            ],
        ];
    }
}