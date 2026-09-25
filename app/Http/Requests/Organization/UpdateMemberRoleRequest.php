<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Enums\WorkspaceRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspaceUser = $this->route('workspace_user');
        return $this->user()?->can('updateRole', $workspaceUser) ?? false;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'in:' . implode(',', WorkspaceRole::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'O papel é obrigatório.',
            'role.in' => 'Papel inválido. Valores permitidos: ' . implode(', ', WorkspaceRole::values()),
        ];
    }
}