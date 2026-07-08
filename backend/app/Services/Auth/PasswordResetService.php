<?php

namespace App\Services\Auth;

class PasswordResetService
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function request(string $email, ?string $ipAddress = null): array
    {
        return $this->authService->requestPasswordReset($email, $ipAddress);
    }

    public function complete(string $resetId, string $newPassword): array
    {
        return $this->authService->completePasswordReset($resetId, $newPassword);
    }
}
