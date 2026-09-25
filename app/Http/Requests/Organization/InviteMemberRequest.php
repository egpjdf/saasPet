<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Enums\WorkspaceRole;
use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');
        return $this->user()?->can('invite', $workspace) ?? false;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:' . implode(',', WorkspaceRole::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Email inválido.',
            'role.required' => 'O papel é obrigatório.',
            'role.in' => 'Papel inválido. Valores permitidos: ' . implode(', ', WorkspaceRole::values()),
        ];
    }
}