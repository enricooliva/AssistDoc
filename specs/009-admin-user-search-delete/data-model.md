# Data Model: Administrative User Search and Soft Delete

## Entity: Enterprise User

**Description**: Existing provisioned user extended with soft-delete metadata
and search-visible identifying fields.

**Fields**:

- `id`: Unique user identifier
- `tenant_id`: Tenant scope identifier
- `name`: Full name used in search and list display
- `email`: Unique sign-in and search identifier
- `role`: Role baseline, with active governance resolved via role assignment
- `status`: Existing lifecycle state (`provisioned`, `active`, `suspended`,
  `locked`, `deactivated`, `reset_pending`)
- `mfa_policy`: Existing MFA requirement indicator
- `deleted_at`: Timestamp indicating soft deletion
- `deleted_by_user_id`: Administrator who performed the soft delete

**Validation Rules**:

- Search-visible identity fields remain required for active records.
- `deleted_by_user_id` must be present when `deleted_at` is set through the
  administrative delete flow.

**Relationships**:

- Belongs to one `Tenant`
- Has one active `RoleAssignment`
- Has many `UserAccessMethod`
- Has many `PasswordResetJourney`
- Has many `MfaChallenge`
- Has many `LockoutRecord`
- Has many `AuditEvent` as actor and affected subject references

**State Notes**:

- Soft delete is orthogonal to lifecycle status.
- Standard user-management flows operate only on non-deleted records.
- Historical references remain valid after deletion.

## Entity: Enterprise User Search Query

**Description**: Tenant-scoped list filter applied to the current administrative
user list.

**Fields**:

- `query`: Free-text search term
- `page`: Requested page number
- `per_page`: Requested page size

**Validation Rules**:

- `query` is optional and trimmed before use
- Empty or whitespace-only search behaves as standard list mode
- Pagination fields follow existing user-list constraints

**Behavior**:

- Search matches name and email within the current tenant scope
- Soft-deleted users are excluded by default

## Entity: Administrative User Deletion Event

**Description**: A governed admin action that soft deletes a user from the
standard active workflow.

**Fields**:

- `target_user_id`: User being removed
- `actor_user_id`: Administrator performing the deletion
- `tenant_id`: Tenant governance context
- `deleted_at`: Deletion timestamp
- `outcome`: `deleted`, `already_deleted`, `denied`, or `not_found`
- `reason`: Optional denial or business explanation

**Validation Rules**:

- Only authorized administrators in scope can create a successful deletion
  event.
- A repeated delete attempt does not mutate the target record again.

**State Transitions**:

- `not_deleted -> soft_deleted`
- `soft_deleted -> soft_deleted` is blocked and reported as repeated delete

**Side Effects**:

- Target user disappears from standard user list and search
- Target user is excluded from active auth flows
- Audit event is recorded with actor, tenant, target, and outcome
