# Quickstart: User Authentication

## Goal

Verify that AssistDoc requires tenant-isolated authentication before entering
the system and that sign-out removes access cleanly.

## Prerequisites

- Docker Compose environment available for backend and frontend
- Backend dependencies installed
- Frontend dependencies installed
- Seeded tenants, users, and role assignments available for local testing
- At least one local-development credential pair for each relevant role

## Setup

1. Start the local application stack.
2. Apply database migrations and seed tenant, user, and role data.
3. Confirm the frontend can reach the backend API.
4. Confirm local-development authentication is enabled for credential-based
   sign-in.

## Manual Verification

### Scenario 1: Successful sign-in

1. Open the sign-in page.
2. Submit valid credentials for an active user.
3. Confirm the user enters the authenticated application area.
4. Confirm the UI shows only data and actions allowed for that user's role.
5. Refresh the page and confirm the authenticated context is restored.

### Scenario 2: Invalid credentials

1. Open the sign-in page.
2. Submit an invalid email or password.
3. Confirm access is denied.
4. Confirm the error message is shown in Italian.
5. Confirm no protected route or data becomes visible.

### Scenario 3: Protected route without auth

1. Open a protected frontend route directly without signing in.
2. Confirm the user is redirected to the sign-in page.
3. Call a protected API endpoint without a valid bearer token.
4. Confirm the API returns the standardized unauthenticated error.

### Scenario 4: Tenant isolation

1. Sign in as a user belonging to tenant A.
2. Confirm the returned authenticated user context identifies tenant A.
3. Attempt to request or manipulate a resource belonging to tenant B.
4. Confirm the backend denies the request and does not leak tenant B data.
5. Confirm the denial is captured in audit history.

### Scenario 5: Sign-out

1. Sign in with valid credentials.
2. Use the sign-out action.
3. Confirm the current client session is invalidated.
4. Navigate back to a protected route.
5. Confirm a fresh sign-in is required.

## Test-First Expectations

- Backend feature tests exist first for login success, login failure,
  unauthenticated access denial, role denial, tenant isolation denial, and
  logout invalidation.
- Frontend component tests exist first for the sign-in form, loading/error
  states, and protected-route handling.
- Playwright coverage exists first for successful sign-in, invalid credentials,
  protected-route redirect, and sign-out.

## Validation Notes

- `php artisan route:list --path=api/v1/auth` confirms the three authentication
  routes are registered.
- `php artisan test tests/Unit/AuthServiceTest.php tests/Unit/AuthorizationServiceTest.php`
  passes in the current environment.
- Full backend feature tests require a PDO SQLite driver for the configured
  in-memory test database.
