<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->authService->userFromToken($request->bearerToken());

        if (! $user) {
            return response()->json([
                'code' => 'unauthorized',
                'message' => 'Autenticazione richiesta o non valida.',
            ], 401);
        }

        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}

