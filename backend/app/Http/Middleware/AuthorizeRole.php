<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeRole
{
    public function __construct(private readonly AuthorizationService $authorizationService)
    {
    }

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->attributes->get('auth_user');

        if (! is_array($user) || ! $this->authorizationService->canAccess($user, $roles)) {
            return response()->json([
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'Operazione non consentita per il ruolo corrente.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
