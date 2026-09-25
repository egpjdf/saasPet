<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', app(config('fortify.password_validation_rules'))],
            'terms' => ['required', 'accepted'],
            'privacy' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Email must be valid',
            'email.unique' => 'Email already registered',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'terms.required' => 'You must accept the terms of service',
            'terms.accepted' => 'You must accept the terms of service',
            'privacy.required' => 'You must accept the privacy policy',
            'privacy.accepted' => 'You must accept the privacy policy',
        ];
    }

    public function user(): \App\Models\User
    {
        return app(\App\Actions\Fortify\CreateNewUser::class)->create($this->validated());
    }
}