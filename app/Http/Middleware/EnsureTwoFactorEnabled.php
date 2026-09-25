<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isPlatformAdmin() || $user->isOrgAdmin()) {
            if (! $user->hasTwoFactorEnabled()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Two-factor authentication is required for administrators',
                        'error' => 'TWO_FACTOR_REQUIRED',
                        'redirect' => route('two-factor.show'),
                    ], 403);
                }

                return redirect()->route('two-factor.show');
            }
        }

        return $next($request);
    }
}