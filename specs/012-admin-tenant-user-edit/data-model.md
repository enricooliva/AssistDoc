# Data Model: Admin, Tenant-Admin Access, and Full User Editing

## Entity: Enterprise User

**Description**: Existing authenticated user record that can be listed, provisioned, and updated by authorized administrators.

**Fields**:

- `id`: Unique user identifier
- `tenant_id`: Tenant scope identifier
- `full_name`: Display and search name used in the admin UI
- `email`: Unique sign-in and contact identifier
- `role`: Governance role within the tenant or platform
- `status`: Lifecycle state (`provisioned`, `active`, `suspended`, `locked`, `deactivated`, `reset_pending`)
- `access_methods`: Array of allowed login methods (`company_account`, `password`)
- `mfa_policy`: MFA policy (`required`, `optional`, `inherited`)
- `password`: Optional new password when password-based access is enabled
- `last_login_at`: Read-only activity timestamp
- `locked_at`: Read-only lock timestamp
- `locked_until`: Read-only lock expiry timestamp
- `lockout_reason`: Read-only lock reason
- `password_reset_required`: Read-only lifecycle flag
- `deleted_at`: Read-only soft-delete timestamp
- `deleted_by_user_id`: Read-only deleter reference

**Editable Fields**:

- `full_name`
- `email`
- `tenant_id` for `super-admin` only
- `role`
- `status`
- `access_methods`
- `mfa_policy`
- `password` when password login is selected

**Validation Rules**:

- `full_name` and `email` are required on edit.
- `email` must remain unique within the user table, excluding the current record.
- `role` must be one of the supported role values for the caller's governance scope.
- `tenant_id` can only change when the caller is `super-admin`.
- `access_methods` must contain at least one supported login method when persisted.
- `password`, if present, must satisfy the existing password policy.
- Read-only lifecycle and audit fields must not be accepted from the edit payload.

**Relationships**:

- Belongs to one `Tenant`
- Has one active `RoleAssignment`
- Has many `UserAccessMethod`
- Has many `PasswordResetJourney`
- Has many `MfaChallenge`
- Has many `LockoutRecord`
- Has many `AuditEvent` as actor and target references

**State Notes**:

- The edit flow updates the same user record used by provisioning and lifecycle management.
- Status and lock fields remain controlled by lifecycle operations, not free-form edits.
- Soft-deleted users remain traceable but are not part of the standard edit path.

## Entity: User Edit Request

**Description**: Payload submitted by the administrator edit form.

**Fields**:

- `full_name`
- `email`
- `tenant_id`
- `role`
- `status`
- `access_methods`
- `mfa_policy`
- `password`

**Validation Rules**:

- The payload is rejected if it contains unexpected fields.
- The backend must normalize access methods and trim textual fields before saving.
- The edit operation must remain tenant-scoped and role-scoped.

## Entity: User Edit Response

**Description**: Canonical server response used to refresh the list row and the edit form after a successful save.

**Fields**:

- `id`
- `full_name`
- `email`
- `tenant`
- `role`
- `status`
- `access_methods`
- `mfa_policy`
- `deleted_at`
- `deleted_by`
- `lockout`

**Behavior**:

- The frontend reloads the updated row after a successful save.
- The edit form should show read-only lifecycle data without allowing mutation.

## Entity: Admin Access Scope

**Description**: Runtime authorization scope that decides whether the caller may access chat, documents, and user editing.

**Fields**:

- `role`: Caller role
- `tenant_id`: Current tenant context
- `permission_set`: Allowed feature areas

**Rules**:

- `super-admin` and `tenant-admin` can access chat and documents for this feature.
- `super-admin` can edit tenant-scoped and cross-tenant user records.
- `tenant-admin` can edit only users in its own tenant context.
- Existing operator and viewer access remains unchanged unless it conflicts with the new tenant-admin allowance.
