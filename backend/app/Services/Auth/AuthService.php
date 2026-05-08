<?php

namespace App\Services\Auth;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $role = str_contains($email, 'admin') ? 'super-admin' : 'viewer';

        return [
            'token' => base64_encode($email.'|tenant-001|'.$role),
            'user' => [
                'id' => 'user-'.substr(md5($email), 0, 8),
                'email' => $email,
                'fullName' => 'Utente AssistDoc',
                'role' => $role,
            ],
            'tenant' => [
                'id' => 'tenant-001',
                'name' => 'AssistDoc Demo',
                'slug' => 'assistdoc-demo',
            ],
        ];
    }

    public function userFromToken(?string $token): ?array
    {
        if (! $token) {
            return null;
        }

        $decoded = explode('|', base64_decode($token, true) ?: '');
        if (count($decoded) !== 3) {
            return null;
        }

        [$email, $tenantId, $role] = $decoded;

        return [
            'id' => 'user-'.substr(md5($email), 0, 8),
            'email' => $email,
            'fullName' => 'Utente AssistDoc',
            'role' => $role,
            'tenant_id' => $tenantId,
        ];
    }
}

