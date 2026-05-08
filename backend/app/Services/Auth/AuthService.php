<?php

namespace App\Services\Auth;

use App\Repositories\UserRepository;
use App\Services\Audit\AuditService;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private const TOKEN_TTL_SECONDS = 3600;

    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditService $auditService,
    ) {
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->users->findActiveByEmail($email);

        if (! $user || $user->auth_provider !== 'local' || ! Hash::check($password, $user->password)) {
            if ($user?->tenant_id) {
                $this->auditService->record(
                    'auth.login_failed',
                    (string) $user->tenant_id,
                    $user->id ? (string) $user->id : null,
                    ['email' => mb_strtolower($email), 'reason' => 'invalid_credentials'],
                    'failure',
                );
            }

            return null;
        }

        $role = $user->roleAssignment?->role ?? $user->role;
        $issuedAt = CarbonImmutable::now();
        $expiresAt = $issuedAt->addSeconds(self::TOKEN_TTL_SECONDS);

        $token = $this->createToken([
            'sub' => (string) $user->id,
            'email' => $user->email,
            'tenant_id' => (string) $user->tenant_id,
            'role' => $role,
            'iat' => $issuedAt->timestamp,
            'exp' => $expiresAt->timestamp,
        ]);

        $user->forceFill(['last_login_at' => now()])->save();

        $this->auditService->record(
            'auth.login_succeeded',
            (string) $user->tenant_id,
            (string) $user->id,
            ['role' => $role],
            'success',
        );

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_TTL_SECONDS,
            'user' => $this->mapUserContext([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'tenant_id' => $user->tenant_id,
                'tenant_name' => $user->tenant?->name,
                'tenant_slug' => $user->tenant?->slug,
            ]),
        ];
    }

    public function logout(?string $token, array $user): void
    {
        if ($token) {
            $context = $this->inspectToken($token);
            $ttl = max(1, (int) (($context['exp'] ?? (time() + self::TOKEN_TTL_SECONDS)) - time()));
            Cache::put($this->revocationKey($token), true, $ttl);
        }

        $this->auditService->record(
            'auth.logout_succeeded',
            (string) $user['tenant_id'],
            (string) $user['id'],
            [],
            'success',
        );
    }

    public function userFromToken(?string $token): ?array
    {
        $context = $this->inspectToken($token);

        if (($context['valid'] ?? false) !== true) {
            return null;
        }

        $user = $this->users->findActiveById($context['user_id']);
        if (! $user || ! $user->tenant || $user->tenant->status !== 'active') {
            return null;
        }

        $role = $user->roleAssignment?->role ?? $user->role;
        if ((string) $user->tenant_id !== (string) $context['tenant_id'] || $role !== ($context['role'] ?? null)) {
            $this->auditService->record(
                'auth.session_denied',
                (string) $user->tenant_id,
                (string) $user->id,
                ['reason' => 'tenant_or_role_mismatch'],
                'denied',
            );

            return null;
        }

        return [
            'id' => (string) $user->id,
            'email' => $user->email,
            'full_name' => $user->name,
            'role' => $role,
            'tenant_id' => (string) $user->tenant_id,
            'tenant_name' => $user->tenant->name,
            'tenant_slug' => $user->tenant->slug,
        ];
    }

    public function inspectToken(?string $token): array
    {
        if (! $token) {
            return ['valid' => false, 'reason' => 'missing_token'];
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return ['valid' => false, 'reason' => 'malformed_token'];
        }

        [$encodedHeader, $encodedPayload, $signature] = $parts;
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (! is_array($payload)) {
            return ['valid' => false, 'reason' => 'invalid_payload'];
        }

        $expected = $this->base64UrlEncode(
            hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $this->signingKey(), true)
        );

        if (! hash_equals($expected, $signature)) {
            return [
                'valid' => false,
                'reason' => 'invalid_signature',
                'tenant_id' => $payload['tenant_id'] ?? null,
                'user_id' => $payload['sub'] ?? null,
            ];
        }

        if (Cache::get($this->revocationKey($token))) {
            return [
                'valid' => false,
                'reason' => 'revoked_token',
                'tenant_id' => $payload['tenant_id'] ?? null,
                'user_id' => $payload['sub'] ?? null,
                'exp' => $payload['exp'] ?? null,
            ];
        }

        if (($payload['exp'] ?? 0) < time()) {
            return [
                'valid' => false,
                'reason' => 'expired_token',
                'tenant_id' => $payload['tenant_id'] ?? null,
                'user_id' => $payload['sub'] ?? null,
                'exp' => $payload['exp'] ?? null,
            ];
        }

        return [
            'valid' => true,
            'user_id' => $payload['sub'] ?? null,
            'tenant_id' => $payload['tenant_id'] ?? null,
            'role' => $payload['role'] ?? null,
            'exp' => $payload['exp'] ?? null,
        ];
    }

    private function mapUserContext(array $user): array
    {
        return [
            'id' => (string) $user['id'],
            'full_name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'tenant' => [
                'id' => (string) $user['tenant_id'],
                'name' => $user['tenant_name'],
                'slug' => $user['tenant_slug'],
            ],
        ];
    }

    private function createToken(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']) ?: '{}');
        $body = $this->base64UrlEncode(json_encode($payload) ?: '{}');
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$body, $this->signingKey(), true));

        return $header.'.'.$body.'.'.$signature;
    }

    private function signingKey(): string
    {
        $appKey = (string) config('app.key', 'assistdoc-dev-key');

        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);

            return $decoded !== false ? $decoded : $appKey;
        }

        return $appKey;
    }

    private function revocationKey(string $token): string
    {
        return 'revoked_token:'.sha1($token);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
