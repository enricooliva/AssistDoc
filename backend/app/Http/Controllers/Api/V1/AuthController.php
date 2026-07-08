<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginOptionsRequest;
use App\Http\Requests\CurrentUserRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\MfaVerifyRequest;
use App\Http\Requests\PasswordLoginRequest;
use App\Http\Requests\PasswordResetCompleteRequest;
use App\Http\Requests\PasswordResetStartRequest;
use App\Http\Requests\LogoutRequest;
use App\Services\Auth\AuthService;
use App\Services\Auth\PasswordResetService;
use App\Services\Auth\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly PasswordResetService $passwordResetService,
        private readonly TenantContextService $tenantContextService,
    ) {
    }

    public function options(LoginOptionsRequest $request): JsonResponse
    {
        return response()->json($this->authService->getLoginOptions());
    }

    public function companyAccountStart(Request $request): JsonResponse
    {
        return response()->json($this->authService->startCompanyAccount());
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

    public function passwordLogin(PasswordLoginRequest $request): JsonResponse
    {
        $result = $this->authService->passwordLogin(
            $request->validated('email'),
            $request->validated('password'),
        );

        if (($result['status'] ?? null) === 'denied') {
            return $this->errorResponse(
                $result['error']['code'] ?? 'UNAUTHENTICATED',
                $result['error']['message'] ?? 'Accesso non riuscito.',
                401
            );
        }

        if (($result['status'] ?? null) === 'action_required') {
            return response()->json([
                'status' => 'action_required',
                'action' => $result['action'],
                'challenge_id' => $result['challenge_id'] ?? null,
                'message' => $result['message'],
                'lockedUntil' => $result['lockedUntil'] ?? null,
            ], 409);
        }

        return response()->json($result);
    }

    public function verifyMfa(MfaVerifyRequest $request): JsonResponse
    {
        $result = $this->authService->verifyMfa(
            $request->validated('challenge_id'),
            $request->validated('verification_code'),
        );

        if (($result['status'] ?? null) === 'denied') {
            return $this->errorResponse(
                $result['error']['code'] ?? 'UNAUTHENTICATED',
                $result['error']['message'] ?? 'Verifica MFA non riuscita.',
                ($result['error']['code'] ?? null) === 'MFA_REQUIRED' ? 409 : 401
            );
        }

        return response()->json($result);
    }

    public function startPasswordReset(PasswordResetStartRequest $request): JsonResponse
    {
        $result = $this->passwordResetService->request(
            $request->validated('email'),
            $request->ip(),
        );

        if (($result['status'] ?? null) === 'denied') {
            return $this->errorResponse(
                $result['error']['code'] ?? 'ACCESS_DENIED',
                $result['error']['message'] ?? 'Reset password non consentito.',
                403
            );
        }

        return response()->json($result, 202);
    }

    public function completePasswordReset(PasswordResetCompleteRequest $request): JsonResponse
    {
        $result = $this->passwordResetService->complete(
            $request->validated('reset_id'),
            $request->validated('new_password'),
        );

        if (($result['status'] ?? null) === 'denied') {
            return $this->errorResponse(
                $result['error']['code'] ?? 'ACCESS_DENIED',
                $result['error']['message'] ?? 'Reset password non valido.',
                409
            );
        }

        return response()->json($result);
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
