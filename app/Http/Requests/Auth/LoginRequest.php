<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
            'two_factor_code' => ['nullable', 'string', 'size:6'],
            'two_factor_recovery_code' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Email must be valid',
            'password.required' => 'Password is required',
            'two_factor_code.size' => 'Two-factor code must be 6 digits',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! auth()->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $this->rateLimit();

            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $user = auth()->user();

        if ($this->hasValidTwoFactor($user)) {
            return;
        }

        if ($user->isPlatformAdmin() || $user->isOrgAdmin()) {
            if (! $user->hasTwoFactorEnabled()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'two_factor_code' => 'Two-factor authentication is required for administrators.',
                ]);
            }

            if (! $this->hasValidTwoFactor($user)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'two_factor_code' => 'Invalid two-factor code.',
                ]);
            }
        }
    }

    private function hasValidTwoFactor($user): bool
    {
        if ($this->filled('two_factor_code')) {
            return $user->two_factor_secret
                && app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class)->confirm($user, $this->input('two_factor_code'));
        }

        if ($this->filled('two_factor_recovery_code')) {
            return $user->two_factor_recovery_codes
                && in_array(strtoupper($this->input('two_factor_recovery_code')), $user->two_factor_recovery_codes, true);
        }

        return false;
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! $this->hasTooManyLoginAttempts()) {
            return;
        }

        $seconds = $this->limiter()->availableIn($this->limiterKey());

        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
        ]);
    }

    public function hasTooManyLoginAttempts(): bool
    {
        return $this->limiter()->tooManyAttempts($this->limiterKey(), 5);
    }

    public function rateLimit(): void
    {
        $this->limiter()->hit($this->limiterKey(), 60);
    }

    public function limiterKey(): string
    {
        return 'login:' . $this->ip();
    }
}