<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrentUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_returns_the_authenticated_user_context(): void
    {
        $token = $this->loginAndReturnToken('viewer@assistdoc.local');

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('user.email', 'viewer@assistdoc.local')
            ->assertJsonPath('user.full_name', 'Viewer Demo')
            ->assertJsonPath('user.tenant.slug', 'assistdoc-demo');
    }

    #[Test]
    public function it_restores_the_current_user_after_password_login(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/password/login', [
            'email' => 'viewer@assistdoc.local',
            'password' => 'password123',
        ])->assertOk();

        $token = (string) $loginResponse->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'viewer@assistdoc.local');
    }

    #[Test]
    public function it_rejects_requests_without_a_valid_token(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    #[Test]
    public function it_rejects_a_token_with_a_spoofed_tenant_context(): void
    {
        $user = User::query()->where('email', 'viewer@assistdoc.local')->firstOrFail();
        $tenantB = User::query()->where('email', 'viewer-b@assistdoc.local')->firstOrFail()->tenant_id;
        $token = $this->makeToken([
            'sub' => (string) $user->id,
            'email' => $user->email,
            'tenant_id' => (string) $tenantB,
            'role' => 'viewer',
            'iat' => time(),
            'exp' => time() + 3600,
        ]);

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    private function loginAndReturnToken(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }

    private function makeToken(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']) ?: '{}');
        $body = $this->base64UrlEncode(json_encode($payload) ?: '{}');
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$body, $this->signingKey(), true));

        return $header.'.'.$body.'.'.$signature;
    }

    private function signingKey(): string
    {
        $key = (string) config('app.key', 'assistdoc-dev-key');

        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7), true) ?: $key;
        }

        return $key;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
