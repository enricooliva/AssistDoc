# Feature Specification: Tenant Administration

**Feature Branch**: `010-tenant-admin`  
**Created**: 2026-05-22  
**Status**: Draft  
**Input**: User description: "Il super amministratore vuole creare un tenant, assegnare un admin iniziale e aggiungere utenti al tenant senza passare dai seed."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create Tenant with Initial Admin (Priority: P1)

A super-admin creates a new tenant and provisions the first tenant administrator in the same flow.

**Why this priority**: This is the entry point for onboarding a new customer or business unit. Without it, a tenant cannot exist in a usable state.

**Independent Test**: Can be tested by creating a tenant from scratch and confirming that the tenant is active and the initial admin can sign in and operate only inside that tenant.

**Acceptance Scenarios**:

1. **Given** a super-admin with access to the platform, **When** they submit a valid tenant name and initial admin details, **Then** the tenant is created and the admin account is associated with it.
2. **Given** a tenant creation request with missing required data or duplicate tenant identity data, **When** the super-admin submits the form, **Then** the tenant is not created and the system shows clear validation feedback.
3. **Given** a newly created tenant with its initial admin, **When** the admin signs in, **Then** the admin sees only data belonging to that tenant.

---

### User Story 2 - Add Users to a Tenant (Priority: P2)

A super-admin adds new users to an existing tenant without relying on database seed data.

**Why this priority**: Ongoing tenant growth depends on being able to provision users after the tenant has been created.

**Independent Test**: Can be tested by adding a user to an existing tenant and confirming that the user appears in that tenant's user list and can access the correct tenant context.

**Acceptance Scenarios**:

1. **Given** an existing active tenant, **When** the super-admin creates a new user for that tenant, **Then** the user is added to the tenant and visible in the tenant user list.
2. **Given** a user creation attempt with an email that is already in use or with an invalid tenant selection, **When** the super-admin submits the form, **Then** the user is not created and the system returns a clear error.
3. **Given** a user added to a tenant, **When** the user signs in, **Then** the user can access only the assigned tenant.

---

### User Story 3 - Review Tenant Membership Summary (Priority: P3)

A super-admin reviews tenant details and membership counts to verify that onboarding is complete.

**Why this priority**: Operational visibility helps confirm that tenants and their users were created correctly, but it is secondary to provisioning.

**Independent Test**: Can be tested by opening the tenant directory and confirming that each tenant shows its current status and membership summary.

**Acceptance Scenarios**:

1. **Given** one or more tenants exist, **When** the super-admin opens the tenant overview, **Then** the system shows each tenant with its current status and member summary.
2. **Given** a tenant with multiple users, **When** the super-admin reviews its details, **Then** the system shows the initial admin and other members in the same tenant context.

---

### Edge Cases

- Attempting to create a tenant without an initial admin must fail.
- Attempting to add a user to a tenant that is inactive or unavailable must be blocked.
- Attempting to add a user with an email already used by another account must be rejected with a clear explanation.
- Attempting to assign a user to more than one tenant at the same time must be prevented unless the product explicitly supports that model in a later release.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST allow a super-admin to create a new tenant with a unique identity and an active initial state.
- **FR-002**: The system MUST require an initial tenant administrator when a tenant is created.
- **FR-003**: The system MUST create the initial administrator as part of the tenant provisioning flow.
- **FR-004**: The system MUST allow a super-admin to add additional users to an existing tenant without using database seed data.
- **FR-005**: The system MUST prevent user creation for an unavailable, inactive, or non-existent tenant.
- **FR-006**: The system MUST prevent duplicate user identities from being assigned in a way that would break tenant ownership rules.
- **FR-007**: The system MUST preserve tenant-scoped access so that users can only see and operate on data belonging to their assigned tenant.
- **FR-008**: The system MUST record audit events for tenant creation, initial admin provisioning, and subsequent user additions.
- **FR-009**: The system MUST present validation errors in a way that allows the super-admin to correct the form and retry the action.
- **FR-010**: The system MUST show tenant membership summaries to the super-admin so that tenant setup can be verified after provisioning.

### API & Contract Requirements *(mandatory for API-backed features)*

- Define a tenant creation endpoint that accepts tenant identity data and initial admin account data in the same request.
- Define a tenant user provisioning endpoint that adds a new user to a specific tenant.
- Define a tenant listing endpoint for the super-admin to review existing tenants and their status.
- Define a tenant detail endpoint that returns the tenant summary and membership overview.
- Specify request and response validation for required tenant data, admin data, and user data.
- Specify standardized error responses for duplicate tenant identity, duplicate user identity, inactive tenant selection, and missing initial admin.
- Specify authentication requirements for all tenant administration endpoints.
- Specify authorization rules so that only super-admin users can create tenants and provision users across tenants.
- State whether this feature introduces a versioned contract update.

### Workflow & State Requirements *(mandatory when entities have lifecycle state)*

- The tenant entity MUST support at least the states `active` and `inactive`.
- Tenant creation MUST result in an `active` tenant unless creation is explicitly blocked by validation.
- The initial admin account MUST be linked to the tenant before the tenant is considered ready for use.
- Additional users MUST inherit the tenant context of the tenant they are created under.
- Every tenant creation and user provisioning action MUST produce an audit record that includes the acting super-admin and the affected tenant or user.
- Tenant activation or deactivation, if included in the same workflow, MUST not silently change the membership of existing users.

### UI & Localization Requirements *(mandatory for frontend work)*

- All UI text MUST be defined for Italian (`it-IT`).
- The super-admin interface MUST let the user create a tenant and its initial admin from a guided form.
- The super-admin interface MUST let the user add users to a selected tenant from the same administration area.
- Validation, loading, and failure states MUST be shown clearly during tenant creation and user provisioning.
- Tenant and user lists, when displayed, MUST remain clearly separated by tenant context and show the relevant tenant name.

### Key Entities *(include if feature involves data)*

- **Tenant**: A customer or organizational boundary with a name, unique identifier, status, and membership set.
- **Tenant Admin**: The first administrative user created for a tenant and responsible for operating within that tenant.
- **Tenant User**: A user account associated with a specific tenant and assigned a role and access scope.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A super-admin can create a new tenant and its initial admin in a single successful flow without manual database changes.
- **SC-002**: A super-admin can add a new user to an existing tenant in one successful flow without using seed data.
- **SC-003**: 90% of successful tenant creation or user provisioning attempts are completed on the first try after correcting validation errors.
- **SC-004**: Support requests related to manual tenant setup are reduced because tenants and users can be provisioned through the administration flow.

## Assumptions

- The feature is limited to super-admin users and does not introduce self-service tenant creation.
- Existing tenant isolation rules remain in place and continue to govern all tenant-scoped data.
- The initial tenant admin is created as part of tenant onboarding rather than selected from another tenant.
- Existing authentication and role management patterns will be reused for the newly provisioned accounts.
- The first release focuses on creating tenants and adding users; deeper tenant lifecycle management can follow later if needed.

## Compliance Notes *(mandatory)*

- This feature must preserve server-side tenant isolation and auditability already present in the platform.
- The simpler alternative of continuing to provision tenants through seeds or manual database changes was rejected because it does not support controlled onboarding or traceable administration.
