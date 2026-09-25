<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorLoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'message' => 'Two-factor authentication required',
        ]);
    }

    public function store(TwoFactorLoginRequest $request): JsonResponse
    {
        $user = $request->user();

        Auth::login($user);

        return response()->json([
            'message' => 'Authenticated with 2FA',
            'user' => $user->load('workspace', 'organization'),
        ]);
    }

    public function enable(): JsonResponse
    {
        $user = auth()->user();

        $provider = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class);
        $data = $provider->create($user);

        return response()->json($data);
    }

    public function confirm(): JsonResponse
    {
        // Handled by Fortify
        return response()->json(['message' => '2FA confirmed']);
    }

    public function disable(): JsonResponse
    {
        $user = auth()->user();

        $provider = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class);
        $provider->disable($user);

        return response()->json([
            'message' => 'Two-factor authentication disabled',
        ]);
    }

    public function recoveryCodes(): JsonResponse
    {
        $user = auth()->user();

        $provider = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class);
        $codes = $provider->generateRecoveryCodes($user);

        return response()->json([
            'recovery_codes' => $codes,
        ]);
    }
}