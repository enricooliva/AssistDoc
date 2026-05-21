# Data Model: Enterprise User Lifecycle

## Enterprise User

- **Purpose**: Represents a provisioned person who may access AssistDoc through
  one or more approved authentication methods within a tenant and role scope.
- **Key fields**:
  - `id`
  - `tenant_id`
  - `name`
  - `email`
  - `auth_provider`
  - `status`
  - `mfa_policy`
  - `last_login_at`
  - `locked_at`
  - `locked_until`
  - `lockout_reason`
  - `password_reset_required`
- **Relationships**:
  - Belongs to one tenant
  - Has one active role assignment per tenant scope
  - Has many password reset journeys
  - Has many MFA challenges
  - Has many lockout records
  - Has many audit events as actor or target
- **Validation rules**:
  - A user must have at least one allowed sign-in method.
  - A user cannot complete sign-in without an active tenant and active role
    assignment.
  - `status` must remain consistent with lifecycle transitions.
  - Password-based sign-in is valid only for accounts provisioned for that
    access method.
- **State transitions**:
  - `provisioned -> active`
  - `active -> suspended`
  - `active -> locked`
  - `locked -> active`
  - `suspended -> active`
  - `active -> deactivated`
  - `active -> reset_pending`
  - `reset_pending -> active`

## Identity Access Method

- **Purpose**: Represents which sign-in path or paths a user is allowed to use.
- **Key fields**:
  - `user_id`
  - `method` (`company_account`, `password`)
  - `enabled`
  - `managed_by`
- **Relationships**:
  - Belongs to one enterprise user
- **Validation rules**:
  - At least one enabled method must exist for an active user.
  - Password reset is allowed only when the `password` method is enabled.
  - Company-account-only users cannot be authenticated through password flows.

## Role Assignment

- **Purpose**: Represents the tenant-scoped authorization role granted to the
  provisioned user.
- **Key fields**:
  - `id`
  - `tenant_id`
  - `user_id`
  - `role`
  - `assigned_by_user_id`
- **Relationships**:
  - Belongs to one tenant
  - Belongs to one enterprise user
- **Validation rules**:
  - The role must be one of `super-admin`, `operator`, or `viewer`.
  - An active session cannot be granted without a valid assignment in the
    target tenant scope.

## Password Reset Journey

- **Purpose**: Represents a managed-password recovery request from initiation
  through completion or expiration.
- **Key fields**:
  - `id`
  - `user_id`
  - `tenant_id`
  - `status` (`initiated`, `completed`, `expired`, `cancelled`)
  - `requested_at`
  - `expires_at`
  - `completed_at`
  - `requested_by_ip`
- **Relationships**:
  - Belongs to one enterprise user
  - Belongs to one tenant
- **Validation rules**:
  - Only users with password access can have active reset journeys.
  - Only one valid active reset journey is allowed at a time per user.
  - Expired or completed journeys cannot be reused.

## MFA Challenge

- **Purpose**: Represents an additional verification step that must be
  satisfied before a session becomes fully authenticated.
- **Key fields**:
  - `id`
  - `user_id`
  - `tenant_id`
  - `authentication_method`
  - `status` (`pending`, `verified`, `failed`, `expired`)
  - `required_by_policy`
  - `initiated_at`
  - `expires_at`
  - `verified_at`
  - `failure_count`
- **Relationships**:
  - Belongs to one enterprise user
  - Belongs to one tenant
- **Validation rules**:
  - A session requiring MFA cannot transition to authenticated until the
    challenge is verified.
  - Expired or failed challenges cannot be reused.
  - Repeated failed MFA attempts can contribute to lockout.

## Lockout Record

- **Purpose**: Represents a security restriction caused by repeated failed
  password or MFA attempts.
- **Key fields**:
  - `id`
  - `user_id`
  - `tenant_id`
  - `trigger_type` (`password_failures`, `mfa_failures`, `admin_action`)
  - `status` (`active`, `released`, `expired`)
  - `failure_threshold`
  - `failure_count`
  - `locked_at`
  - `locked_until`
  - `released_at`
  - `released_by_user_id`
- **Relationships**:
  - Belongs to one enterprise user
  - Belongs to one tenant
  - May belong to one releasing administrator
- **Validation rules**:
  - A user with an active lockout cannot complete sign-in.
  - Only authorized administrators can manually release a lockout.
  - Company-account primary-auth failures are not counted by this record unless
    they occur in AssistDoc-controlled steps after upstream authentication.

## Unified Login Session

- **Purpose**: Represents the in-progress authentication state used by the
  single login page before a JWT session is fully issued.
- **Key fields**:
  - `selected_method`
  - `primary_auth_status`
  - `mfa_status`
  - `final_session_status`
  - `denial_reason`
- **Relationships**:
  - References one enterprise user when identity lookup succeeds
- **Validation rules**:
  - A JWT session may be issued only when primary authentication succeeds, the
    user lifecycle allows access, and any required MFA step is satisfied.
