<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Login page',
        ]);
    }

    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Check 2FA requirement for admins
        if ($this->requiresTwoFactor($user)) {
            return response()->json([
                'message' => 'Two-factor authentication required',
                'two_factor_required' => true,
                'user_id' => $user->id,
            ], 200);
        }

        return response()->json([
            'message' => 'Authenticated',
            'user' => $user->load('workspace', 'organization'),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    private function requiresTwoFactor($user): bool
    {
        if (! $user) {
            return false;
        }

        return ($user->isPlatformAdmin() || $user->isOrgAdmin())
            && ! $user->hasTwoFactorEnabled();
    }
}