<?php

declare(strict_types=1);

return [

    'guard' => 'web',

    'passwords' => 'users',

    'username' => 'email',

    'email' => 'email',

    'features' => [
        \Laravel\Fortify\Features::registration(),
        \Laravel\Fortify\Features::resetPasswords(),
        \Laravel\Fortify\Features::emailVerification(),
        \Laravel\Fortify\Features::twoFactorAuthentication([
            'confirm' => true,
            'confirm_password' => true,
        ]),
    ],

    'actions' => [
        \App\Actions\Fortify\CreateNewUser::class,
        \App\Actions\Fortify\ResetUserPassword::class,
        \App\Actions\Fortify\UpdateUserPassword::class,
        \App\Actions\Fortify\UpdateUserProfileInformation::class,
        \App\Actions\Fortify\EnableTwoFactorAuthentication::class,
        \App\Actions\Fortify\DisableTwoFactorAuthentication::class,
        \App\Actions\Fortify\ConfirmTwoFactorAuthentication::class,
    ],

    'two_factor' => [
        'confirm' => true,
        'confirm_password' => true,
    ],

    'password_validation_rules' => [
        'required',
        'string',
        'min:12',
        'max:255',
        'confirmed',
        new \App\Rules\PasswordNotPwned,
    ],

    'register_view' => 'auth.register',

    'login_view' => 'auth.login',

    'verify_email_view' => 'auth.verify-email',

    'reset_password_view' => 'auth.reset-password',

    'confirm_password_view' => 'auth.confirm-password',

    'two_factor_login_view' => 'auth.two-factor-login',

    'register_route' => 'register',

    'login_route' => 'login',

    'logout_route' => 'logout',

    'verify_email_route' => 'verification.verify',

    'reset_password_route' => 'password.reset',

    'confirm_password_route' => 'password.confirm',

    'two_factor_login_route' => 'two-factor.login',

];