<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use App\Services\Auth\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly TenantContextService $tenantContextService,
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        return response()->json($this->authService->login($validated['email'], $validated['password']));
    }

    public function logout(): JsonResponse
    {
        return response()->json([], 204);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json([
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'fullName' => $user['fullName'],
                'role' => $user['role'],
            ],
            'tenant' => $this->tenantContextService->fromUser($user),
        ]);
    }
}

