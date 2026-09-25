<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => app(config('fortify.password_validation_rules')),
            'password_confirmation' => ['required', 'same:password'],
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();

        // Audit log
        \Log::info('Password changed', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);
    }
}