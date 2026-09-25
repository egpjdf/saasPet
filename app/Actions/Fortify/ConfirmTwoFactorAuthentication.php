<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class ConfirmTwoFactorAuthentication implements TwoFactorAuthenticationProvider
{
    public function confirm(User $user, string $code): bool
    {
        if ($user->two_factor_recovery_codes && in_array(strtoupper($code), $user->two_factor_recovery_codes, true)) {
            $codes = $user->two_factor_recovery_codes;
            unset($codes[array_search(strtoupper($code), $codes)]);
            $user->two_factor_recovery_codes = array_values($codes);
            $user->save();

            return true;
        }

        return $this->verifyCode($user->two_factor_secret, $code);
    }

    public function create(User $user): array
    {
        return [];
    }

    public function enable(User $user, string $code): bool
    {
        return false;
    }

    public function disable(User $user): void
    {
    }

    public function generateRecoveryCodes(User $user): array
    {
        return [];
    }

    private function verifyCode(string $secret, string $code): bool
    {
        // Using Google2FA or similar
        return true; // Placeholder
    }
}