<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\MfaChallengeRepository;
use App\Repositories\PasswordResetJourneyRepository;
use App\Repositories\UserRepository;
use App\Services\Audit\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    private const TOKEN_TTL_SECONDS = 3600;

    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordResetJourneyRepository $passwordResetJourneys,
        private readonly MfaChallengeRepository $mfaChallenges,
        private readonly MfaLockoutService $mfaLockoutService,
        private readonly AuditService $auditService,
    ) {
    }

    public function getLoginOptions(): array
    {
        return [
            'options' => [
                [
                    'id' => 'company_account',
                    'label' => 'Accedi con account aziendale',
                    'description' => 'Reindirizzamento al provider identita aziendale.',
                ],
                [
                    'id' => 'password',
                    'label' => 'Accedi con email e password',
                    'description' => 'Accesso gestito direttamente da AssistDoc.',
                ],
            ],
        ];
    }

    public function startCompanyAccount(): array
    {
        return [
            'status' => 'redirect_required',
            'redirect_url' => (string) config('app.url', 'http://localhost:8000').'/sign-in',
        ];
    }

    public function login(string $email, string $password): ?array
    {
        $response = $this->passwordLogin($email, $password);

        return ($response['status'] ?? null) === 'authenticated' ? $response : null;
    }

    public function passwordLogin(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if (! $user || ! $this->users->hasAccessMethod($user, 'password') || ! Hash::check($password, (string) $user->password)) {
            if ($user?->tenant_id) {
                $this->auditService->record(
                    'auth.login_failed',
                    (string) $user->tenant_id,
                    $user->id ? (string) $user->id : null,
                    ['email' => mb_strtolower($email), 'reason' => 'invalid_credentials'],
                    'failure',
                );

                $this->mfaLockoutService->recordPasswordFailure($user);
            }

            return [
                'status' => 'denied',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Credenziali non valide o accesso non consentito.',
                ],
            ];
        }

        if ($lockout = $this->mfaLockoutService->activeLockoutFor($user)) {
            return $this->lockedResponse($user, $lockout['lockedUntil'] ?? null);
        }

        $lifecycleBlock = $this->validateLifecycle($user, 'password');
        if ($lifecycleBlock !== null) {
            return $lifecycleBlock;
        }

        if ($user->password_reset_required) {
            return [
                'status' => 'action_required',
                'action' => 'password_reset_required',
                'message' => 'Per continuare devi reimpostare la password.',
            ];
        }

        if ($this->requiresMfa($user)) {
            $challenge = $this->mfaChallenges->create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'authentication_method' => 'password',
                'status' => 'pending',
                'required_by_policy' => true,
                'verification_code_hash' => Hash::make($this->mfaCode()),
                'initiated_at' => now(),
                'expires_at' => now()->addMinutes(10),
            ]);

            $this->auditService->record(
                'auth.mfa_challenge_started',
                (string) $user->tenant_id,
                (string) $user->id,
                ['challenge_id' => (string) $challenge->id, 'method' => 'password'],
                'success',
                'user',
                $user->id
            );

            return [
                'status' => 'action_required',
                'action' => 'mfa_required',
                'challenge_id' => (string) $challenge->id,
                'message' => 'Completa la verifica MFA per accedere.',
            ];
        }

        $this->mfaLockoutService->clearPasswordFailures($user);

        return $this->authenticatedResponse($user);
    }

    public function verifyMfa(string $challengeId, string $verificationCode): array
    {
        $challenge = $this->mfaChallenges->findPending($challengeId);

        if (! $challenge) {
            return [
                'status' => 'denied',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'La verifica MFA non è valida.',
                ],
            ];
        }

        if ($challenge->expires_at !== null && $challenge->expires_at->isPast()) {
            $challenge->status = 'expired';
            $this->mfaChallenges->save($challenge);

            return [
                'status' => 'denied',
                'error' => [
                    'code' => 'MFA_REQUIRED',
                    'message' => 'La verifica MFA è scaduta.',
                ],
            ];
        }

        if (! Hash::check($verificationCode, $challenge->verification_code_hash)) {
            $challenge->failure_count++;
            $this->mfaChallenges->save($challenge);
            $this->mfaLockoutService->recordMfaFailure($challenge->user);

            if ($lockout = $this->mfaLockoutService->activeLockoutFor($challenge->user)) {
                return $this->lockedResponse($challenge->user, $lockout['lockedUntil'] ?? null);
            }

            return [
                'status' => 'denied',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Il codice MFA non è valido.',
                ],
            ];
        }

        $challenge->status = 'verified';
        $challenge->verified_at = now();
        $this->mfaChallenges->save($challenge);

        $this->auditService->record(
            'auth.mfa_verified',
            (string) $challenge->tenant_id,
            (string) $challenge->user_id,
            ['challenge_id' => (string) $challenge->id],
            'success',
            'user',
            $challenge->user_id
        );

        $this->mfaLockoutService->clearMfaFailures($challenge->user);
        $this->mfaLockoutService->clearPasswordFailures($challenge->user);

        return $this->authenticatedResponse($challenge->user);
    }

    public function requestPasswordReset(string $email, ?string $ipAddress = null): array
    {
        $user = $this->users->findByEmail($email);

        if (! $user || ! $this->users->hasAccessMethod($user, 'password')) {
            return [
                'status' => 'denied',
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'Il reset password non è disponibile per questo account.',
                ],
            ];
        }

        $this->passwordResetJourneys->invalidateActiveForUser($user->id);
        $journey = $this->passwordResetJourneys->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'status' => 'initiated',
            'reset_token' => Str::uuid()->toString(),
            'requested_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'requested_by_ip' => $ipAddress,
        ]);

        $user->password_reset_required = true;
        $this->users->save($user);

        $this->auditService->record(
            'auth.password_reset_started',
            (string) $user->tenant_id,
            (string) $user->id,
            ['reset_id' => (string) $journey->id],
            'success',
            'user',
            $user->id
        );

        return [
            'status' => 'accepted',
            'message' => 'Richiesta di reimpostazione password registrata.',
            'reset_id' => $journey->reset_token,
        ];
    }

    public function completePasswordReset(string $resetToken, string $newPassword): array
    {
        $journey = $this->passwordResetJourneys->findValidByToken($resetToken);

        if (! $journey || $journey->expires_at !== null && $journey->expires_at->isPast()) {
            return [
                'status' => 'denied',
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'Il reset password non è più valido.',
                ],
            ];
        }

        $user = $journey->user;
        if (! $user || ! $this->users->hasAccessMethod($user, 'password')) {
            return [
                'status' => 'denied',
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'Il reset password non è consentito per questo account.',
                ],
            ];
        }

        $user->password = Hash::make($newPassword);
        $user->password_reset_required = false;
        if ($user->status === 'reset_pending') {
            $user->status = 'active';
        }
        $this->users->save($user);

        $journey->status = 'completed';
        $journey->completed_at = now();
        $this->passwordResetJourneys->save($journey);

        $this->auditService->record(
            'auth.password_reset_completed',
            (string) $user->tenant_id,
            (string) $user->id,
            ['reset_id' => (string) $journey->id],
            'success',
            'user',
            $user->id
        );

        return [
            'status' => 'completed',
            'message' => 'Password aggiornata correttamente.',
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

    public function authenticatedResponse(User $user): array
    {
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
            'status' => 'authenticated',
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

    private function validateLifecycle(User $user, string $method): ?array
    {
        if (! $this->users->hasAccessMethod($user, $method)) {
            return [
                'status' => 'denied',
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'Il metodo di accesso selezionato non è disponibile per questo account.',
                ],
            ];
        }

        if (! $user->tenant || $user->tenant->status !== 'active' || ! $user->roleAssignment) {
            return [
                'status' => 'denied',
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'L\'account non ha un contesto tenant o ruolo valido.',
                ],
            ];
        }

        if (in_array($user->status, ['suspended', 'deactivated'], true)) {
            return [
                'status' => 'denied',
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'L\'account non è autorizzato ad accedere.',
                ],
            ];
        }

        if ($user->status === 'locked' || ($user->locked_until !== null && $user->locked_until->isFuture())) {
            return $this->lockedResponse($user, $user->locked_until?->toIso8601String());
        }

        return null;
    }

    private function requiresMfa(User $user): bool
    {
        return in_array($user->mfa_policy, ['required', 'inherited'], true);
    }

    private function lockedResponse(User $user, ?string $lockedUntil): array
    {
        return [
            'status' => 'action_required',
            'action' => 'account_locked',
            'message' => 'L\'account è temporaneamente bloccato.',
            'lockedUntil' => $lockedUntil,
        ];
    }

    private function mfaCode(): string
    {
        return '123456';
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
