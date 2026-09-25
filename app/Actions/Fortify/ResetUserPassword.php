<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => app(config('fortify.password_validation_rules')),
            'password_confirmation' => ['required', 'same:password'],
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();

        // Audit log
        \Log::info('Password reset', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);
    }
}