<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequirePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->isPlatformAdmin()) {
            return response()->json([
                'message' => 'Platform admin access required',
                'error' => 'PLATFORM_ADMIN_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}