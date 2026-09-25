<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class EnableTwoFactorAuthentication implements TwoFactorAuthenticationProvider
{
    public function create(User $user): array
    {
        $user->forceFill([
            'two_factor_secret' => $secret = $this->generateSecret(),
            'two_factor_recovery_codes' => array_map('strtoupper', collect(range(1, 8))->map(fn () => $this->generateRecoveryCode())->toArray()),
        ])->save();

        return [
            'secret' => $secret,
            'recovery_codes' => $user->two_factor_recovery_codes,
            'qr_code' => $this->generateQrCode($user, $secret),
        ];
    }

    public function enable(User $user, string $code): bool
    {
        Validator::make(['code' => $code], [
            'code' => ['required', 'string', 'size:6'],
        ])->validate();

        if (! $this->verifyCode($user->two_factor_secret, $code)) {
            return false;
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        // Audit log
        \Log::info('2FA enabled', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);

        return true;
    }

    public function confirm(User $user, string $code): bool
    {
        return $this->verifyCode($user->two_factor_secret, $code);
    }

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

    public function generateRecoveryCodes(User $user): array
    {
        $codes = array_map('strtoupper', collect(range(1, 8))->map(fn () => $this->generateRecoveryCode())->toArray());

        $user->forceFill([
            'two_factor_recovery_codes' => $codes,
        ])->save();

        return $codes;
    }

    private function generateSecret(): string
    {
        return base64_encode(random_bytes(10));
    }

    private function generateRecoveryCode(): string
    {
        return substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8);
    }

    private function verifyCode(string $secret, string $code): bool
    {
        // Using Google2FA or similar
        return true; // Placeholder - implement with actual 2FA library
    }

    private function generateQrCode(User $user, string $secret): string
    {
        // Generate QR code for authenticator apps
        return 'data:image/png;base64,' . base64_encode('placeholder');
    }
}