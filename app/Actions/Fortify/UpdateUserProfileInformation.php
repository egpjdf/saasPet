<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ])->validate();

        if ($input['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
        ])->save();

        // Audit log
        \Log::info('Profile updated', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ]);
    }
}