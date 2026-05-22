# Data Model: Tenant Administration

## Entity: Tenant

**Description**: A customer or organizational boundary that owns users and tenant-scoped content.

**Fields**:

- `id`: Unique tenant identifier
- `name`: Human-readable tenant name
- `slug`: Unique stable tenant key
- `status`: Tenant lifecycle state, at minimum `active` or `inactive`

**Validation Rules**:

- Tenant name and slug must be unique enough to avoid ambiguous administration.
- A tenant used for onboarding must be created in an active state unless validation blocks creation.

**Relationships**:

- Has many `User`
- Has many `RoleAssignment`
- Has many `AuditEvent`

## Entity: Tenant Provisioning Request

**Description**: Administrative request that creates a tenant and its initial admin together.

**Fields**:

- `tenant_name`: Name for the new tenant
- `tenant_slug`: Unique tenant key or equivalent identifier
- `admin_full_name`: Full name of the first admin
- `admin_email`: Email for the first admin
- `admin_role`: Typically `super-admin`
- `admin_access_methods`: Allowed sign-in methods for the first admin
- `admin_status`: Initial account state

**Validation Rules**:

- An initial admin is mandatory.
- The request must fail if tenant identity data is incomplete or duplicated.
- The request must fail if the admin email cannot be safely assigned.

## Entity: Tenant User Provisioning Request

**Description**: Administrative request that adds a new user to an existing tenant.

**Fields**:

- `tenant_id`: Target tenant
- `full_name`: User name
- `email`: Unique user email
- `role`: Assigned role in the tenant
- `access_methods`: Allowed sign-in methods
- `status`: Initial lifecycle state
- `mfa_policy`: MFA requirement

**Validation Rules**:

- The target tenant must exist and be active.
- The request must fail if the user identity conflicts with an existing account.
- The user must be attached to exactly one tenant for this feature.

## Entity: Tenant Membership Summary

**Description**: Read-only summary used for operational verification.

**Fields**:

- `tenant_id`: Tenant identifier
- `tenant_name`: Tenant display name
- `status`: Tenant state
- `member_count`: Number of users in the tenant
- `admin_count`: Number of users with admin-level roles
- `last_provisioned_at`: Most recent provisioning timestamp

**Relationships**:

- Derived from `Tenant`, `User`, and `RoleAssignment`

## State Notes

- Tenant states are limited to active/inactive for this release.
- A tenant becomes usable only after the initial admin is provisioned.
- User provisioning inherits the selected tenant context and does not alter other tenants.
