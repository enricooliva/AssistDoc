<?php

namespace Tests\Feature\Support;

use Tests\TestCase;

final class EnterpriseUserAccessFixtures
{
    public static function login(TestCase $testCase, string $email): string
    {
        return (string) $testCase->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }
}
