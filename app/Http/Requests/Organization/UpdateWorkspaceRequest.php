<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Enums\WorkspaceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');
        return $this->user()?->can('update', $workspace) ?? false;
    }

    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:100', 'alpha_dash', Rule::unique('workspaces', 'slug')->where('organization_id', $workspace->organization_id)->ignore($workspace->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(WorkspaceStatus::values())],
            'settings' => ['sometimes', 'array'],
            'settings.timezone' => ['nullable', 'string', 'max:50'],
            'settings.locale' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.alpha_dash' => 'O slug deve conter apenas letras, números, hífens e underscores.',
            'slug.unique' => 'Este slug já está em uso nesta organização.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => str($this->slug)->lower()->slug(),
            ]);
        }
    }
}