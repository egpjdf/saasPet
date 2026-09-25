<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Organization $this */
        return [
            'id' => (string) $this->id,
            'type' => 'organizations',
            'attributes' => [
                'name' => $this->name,
                'slug' => $this->slug,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'status' => $this->status?->value,
                'settings' => $this->settings,
                'trial_ends_at' => $this->trial_ends_at?->toISOString(),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'deleted_at' => $this->deleted_at?->toISOString(),
            ],
            'relationships' => [
                'workspaces' => [
                    'links' => [
                        'related' => route('api.organizations.workspaces.index', $this),
                    ],
                ],
            ],
            'links' => [
                'self' => route('api.organizations.show', $this),
            ],
        ];
    }
}