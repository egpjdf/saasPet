<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6'],
            'recovery_code' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Two-factor code is required',
            'code.size' => 'Two-factor code must be 6 digits',
        ];
    }

    public function user(): \App\Models\User
    {
        $user = auth()->user();

        if (! $user) {
            throw new \Exception('User not authenticated');
        }

        $provider = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class);

        if (! $provider->confirm($user, $this->input('code'))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => 'Invalid two-factor code.',
            ]);
        }

        return $user;
    }
}