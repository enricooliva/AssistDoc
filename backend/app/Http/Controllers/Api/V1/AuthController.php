<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CurrentUserRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\LogoutRequest;
use App\Services\Auth\AuthService;
use App\Services\Auth\TenantContextService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly TenantContextService $tenantContextService,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $session = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        if (! $session) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Credenziali non valide o accesso non consentito.',
                ],
            ], 401);
        }

        return response()->json($session);
    }

    public function logout(LogoutRequest $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $this->authService->logout($request->bearerToken(), $user);

        return response()->json([
            'message' => 'Logout completed',
        ]);
    }

    public function me(CurrentUserRequest $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json([
            'user' => [
                'id' => (string) $user['id'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'tenant' => $this->tenantContextService->fromUser($user),
            ],
        ]);
    }
}
