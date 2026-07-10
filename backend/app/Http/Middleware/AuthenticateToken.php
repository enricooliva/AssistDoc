<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthService;
use App\Services\Audit\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AuditService $auditService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->authService->userFromToken($request->bearerToken());

        if (! $user) {
            $tokenContext = $this->authService->inspectToken($request->bearerToken());

            if ($tokenContext['tenant_id'] ?? null) {
                $this->auditService->record(
                    'auth.session_denied',
                    (string) $tokenContext['tenant_id'],
                    isset($tokenContext['user_id']) ? (string) $tokenContext['user_id'] : null,
                    ['reason' => $tokenContext['reason'] ?? 'missing_or_invalid_token'],
                    'denied',
                );
            }

            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Autenticazione richiesta o non valida.',
                ],
            ], 401);
        }

        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}
