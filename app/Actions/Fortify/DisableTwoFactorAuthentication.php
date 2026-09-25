<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class DisableTwoFactorAuthentication implements TwoFactorAuthenticationProvider
{
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        // Audit log
        \Log::info('2FA disabled', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);
    }

    public function create(User $user): array
    {
        return [];
    }

    public function enable(User $user, string $code): bool
    {
        return false;
    }

    public function confirm(User $user, string $code): bool
    {
        return false;
    }

    public function generateRecoveryCodes(User $user): array
    {
        return [];
    }
}