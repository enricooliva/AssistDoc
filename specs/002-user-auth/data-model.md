# Data Model: User Authentication

## Tenant

**Purpose**: Security boundary that owns users, role assignments, audit events,
and every protected resource in the platform.

**Fields**

- `id`: unique identifier
- `name`: tenant display name
- `slug`: tenant key used for internal routing and audit clarity
- `status`: active, suspended
- `created_at`
- `updated_at`

**Relationships**

- Has many `User`
- Has many `RoleAssignment`
- Has many `AuthenticationAuditEvent`

**Validation**

- `name` required, 2-120 chars
- `slug` required, unique, lowercase URL-safe
- `status` required

## User

**Purpose**: Registered person who may authenticate into exactly one tenant
context in MVP scope.

**Fields**

- `id`
- `tenant_id`
- `email`
- `full_name`
- `auth_provider`: local, sso
- `status`: invited, active, disabled
- `last_login_at`
- `created_at`
- `updated_at`

**Relationships**

- Belongs to `Tenant`
- Has one active `RoleAssignment`
- Has many `AuthenticationSession`
- Has many `AuthenticationAuditEvent`

**Validation**

- `tenant_id` required
- `email` required, normalized, unique within tenant
- `full_name` required, 2-120 chars
- `auth_provider` required
- `status` required

## RoleAssignment

**Purpose**: RBAC mapping that defines what a user can access inside their
tenant.

**Fields**

- `id`
- `tenant_id`
- `user_id`
- `role`: super-admin, operator, viewer
- `assigned_by_user_id`
- `created_at`
- `updated_at`

**Relationships**

- Belongs to `Tenant`
- Belongs to `User`

**Validation**

- `tenant_id` must match the related user tenant
- Only one active role assignment per user in MVP scope
- `role` must be one of `super-admin`, `operator`, `viewer`

## AuthenticationSession

**Purpose**: Represents the current or historical authenticated access context
for a user on a specific client.

**Fields**

- `id`
- `tenant_id`
- `user_id`
- `state`: unauthenticated, authenticated, invalidated
- `issued_at`
- `invalidated_at`
- `invalidation_reason`: logout, expiry, revocation
- `client_label`
- `created_at`
- `updated_at`

**Relationships**

- Belongs to `Tenant`
- Belongs to `User`

**Validation**

- `tenant_id` required and must match the related user tenant
- `state` required
- `invalidation_reason` required when `state` is `invalidated`

**State Transitions**

- `unauthenticated -> authenticated` on successful login
- `authenticated -> invalidated` on explicit logout
- `authenticated -> invalidated` on expiry or revocation
- `invalidated -> authenticated` only through a new login represented by a new
  active session

## AuthenticatedUserContext

**Purpose**: Read model returned to the SPA after authentication succeeds or
when restoring an existing session.

**Fields**

- `user_id`
- `tenant_id`
- `tenant_name`
- `full_name`
- `email`
- `role`
- `permissions_summary`

**Relationships**

- Derived from `User`, `Tenant`, and `RoleAssignment`

**Validation**

- Must be produced only for active users with an active tenant and role
- `tenant_id` and `role` are mandatory

## AuthenticationAuditEvent

**Purpose**: Immutable security record of login, logout, and denied-access
outcomes.

**Fields**

- `id`
- `tenant_id`
- `user_id`: optional for failed login before identity resolution
- `event_type`: login_succeeded, login_failed, logout_succeeded,
  session_denied, role_denied
- `actor_identifier`
- `outcome`: success, failure, denied
- `reason`
- `occurred_at`

**Relationships**

- Belongs to `Tenant` when tenant is known
- Optionally belongs to `User`

**Validation**

- `event_type`, `outcome`, and `occurred_at` required
- `reason` required for failed or denied outcomes
- Event tenant must match user tenant whenever user is resolved
