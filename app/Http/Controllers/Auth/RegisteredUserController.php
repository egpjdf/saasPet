<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Registration page',
        ]);
    }

    public function store(RegisterRequest $request): JsonResponse
    {
        $user = $request->user();

        Auth::login($user);

        return response()->json([
            'message' => 'Registered successfully',
            'user' => $user->load('workspace', 'organization'),
        ], 201);
    }
}