<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\OrganizationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->route('organization');
        return $this->user()?->can('update', $organization) ?? false;
    }

    public function rules(): array
    {
        $organization = $this->route('organization');
        $organizationId = $organization?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:100', 'alpha_dash', Rule::unique('organizations', 'slug')->ignore($organizationId)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(OrganizationStatus::values())],
            'settings' => ['sometimes', 'array'],
            'settings.primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'settings.secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'settings.timezone' => ['nullable', 'string', 'max:50'],
            'settings.locale' => ['nullable', 'string', 'max:10'],
            'settings.domain' => ['nullable', 'string', 'max:255', 'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i'],
            'settings.subdomain' => ['nullable', 'string', 'max:63', 'alpha_dash', Rule::unique('organizations', 'settings->subdomain')->ignore($organizationId)],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.alpha_dash' => 'O slug deve conter apenas letras, números, hífens e underscores.',
            'slug.unique' => 'Este slug já está em uso.',
            'settings.primary_color.regex' => 'A cor primária deve ser um hex válido (ex: #FF5733).',
            'settings.secondary_color.regex' => 'A cor secundária deve ser um hex válido (ex: #FF5733).',
            'settings.domain.regex' => 'Domínio inválido.',
            'settings.subdomain.alpha_dash' => 'O subdomínio deve conter apenas letras, números, hífens e underscores.',
            'settings.subdomain.unique' => 'Este subdomínio já está em uso.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => str($this->slug)->lower()->slug(),
            ]);
        }

        if ($this->has('settings.subdomain')) {
            $this->merge([
                'settings.subdomain' => str($this->settings['subdomain'])->lower()->slug(),
            ]);
        }
    }
}