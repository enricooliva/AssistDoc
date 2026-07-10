# Research: User Authentication

## Authentication Contract

- Decision: Use one versioned REST authentication contract with three endpoints:
  login, logout, and current-user context.
- Rationale: This is the smallest complete surface that supports sign-in,
  sign-out, guarded SPA bootstrapping, and contract-first testing.
- Alternatives considered:
  - Login-only endpoint with frontend-stored user profile: rejected because the
    frontend would not have a reliable server-sourced access context.
  - Separate local and hosted auth contracts: rejected because it fragments the
    API surface and test matrix.

## Token Strategy

- Decision: Use stateless JWT bearer tokens for authenticated API requests and
  invalidate the active client session on logout by revoking the presented
  token or marking it unusable according to the backend auth mechanism.
- Rationale: This matches the constitution and keeps the Angular client fully
  API-driven without server-rendered session state.
- Alternatives considered:
  - Session cookies: rejected because they violate the constitution's stateless
    authentication rule.
  - Long-lived tokens without explicit logout invalidation: rejected because it
    weakens sign-out guarantees on shared devices.

## Tenant Isolation Enforcement

- Decision: Resolve tenant context server-side from the authenticated user and
  enforce it in middleware, services, repositories, and audit events. The
  client may display tenant information but must never be the source of truth
  for tenant selection during protected operations.
- Rationale: Tenant isolation is a security boundary. Enforcing it only in UI
  or only in repositories leaves gaps for direct API calls, background flows,
  and service orchestration.
- Alternatives considered:
  - Client-supplied tenant identifier on each request: rejected because it is
    vulnerable to spoofing and cross-tenant access attempts.
  - Repository-only tenant filtering: rejected because service-level
    authorization and audit logic also need tenant awareness.

## Role Evaluation

- Decision: Evaluate both authentication and RBAC on every protected request,
  with endpoint-level role declarations and service-level checks for workflow
  actions that need finer rules.
- Rationale: Endpoint guards stop broad misuse early, while service checks
  preserve domain invariants and prevent authorization drift.
- Alternatives considered:
  - UI-only hiding of disallowed actions: rejected because it does not secure
    the API.
  - Service-only authorization with no endpoint declarations: rejected because
    it weakens clarity and contract-first verification.

## Audit Scope

- Decision: Record immutable audit events for successful login, failed login,
  logout, expired-token denial, and role-based access denial.
- Rationale: These events are the minimum set needed to investigate account use
  and access failures without over-logging unrelated UI behavior.
- Alternatives considered:
  - Log only successful logins and logouts: rejected because failed attempts and
    access denials are security-relevant.
  - Log every frontend interaction: rejected because it adds noise without
    improving the auth security trail.

## Frontend Session Behavior

- Decision: Bootstrap the SPA by requesting the authenticated user context when
  a token is present, redirect unauthenticated access attempts to the sign-in
  page, and clear local auth state immediately on logout or invalid-session
  response.
- Rationale: This keeps route protection consistent with backend truth and gives
  users predictable recovery when a session expires.
- Alternatives considered:
  - Assume a stored token is valid until an action fails: rejected because it
    creates broken navigation states.
  - Re-fetch user context on every route transition only: rejected because it
    adds unnecessary latency to normal navigation.
