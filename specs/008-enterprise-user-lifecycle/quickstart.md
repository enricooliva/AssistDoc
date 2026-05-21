# Quickstart: Enterprise User Lifecycle

## Backend verification

1. Run the backend auth and user-management tests:

```bash
cd backend
php artisan test --filter="UnifiedLoginTest|EnterpriseUserManagementTest|PasswordRecoveryTest|CurrentUserTest|AuthorizationTest|TenantIsolationTest"
```

2. Run the broader enterprise-user lifecycle tests once they exist:

```bash
cd backend
php artisan test --filter="EnterpriseUserLifecycleServiceTest|PasswordResetServiceTest|MfaLockoutServiceTest"
```

## Frontend verification

1. Run the Angular unit tests:

```bash
cd frontend
npm test
```

2. Run the Playwright end-to-end suite:

```bash
cd frontend
npm run e2e
```

## Manual smoke test

1. Sign in as an authorized administrator.
2. Open the unified login page and confirm it shows:
   - `Accedi con account aziendale`
   - `Accedi con email e password`
3. Provision a new managed-password user with tenant, role, lifecycle status,
   and MFA policy.
4. Confirm the new user can reach the login page, sign in with email and
   password, and is stopped for MFA when policy requires it.
5. Trigger repeated invalid password or MFA attempts and confirm the account
   enters lockout with clear Italian feedback.
6. Unlock the account as an administrator and confirm access works again.
7. Trigger a password reset for the managed-password user and confirm the reset
   can be completed successfully.
8. Confirm that a company-account-only user is denied the password-reset flow.
9. Suspend or deactivate a provisioned user and confirm sign-in is denied even
   when the primary identity check succeeds.
10. Review audit history and confirm provisioning, login outcomes, reset
    completion, MFA outcomes, lockout, and unlock actions are recorded.
11. Confirm the `/users` area shows tenant-scoped pagination and the Italian
    status messages for provisioning, status change, and unlock outcomes.
