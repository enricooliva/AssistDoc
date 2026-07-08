# Research: Tenant Administration

## Decision 1: Add dedicated tenant administration endpoints instead of overloading seed-based setup

**Decision**: Implement tenant creation and tenant-scoped provisioning through authenticated super-admin endpoints.

**Rationale**: The current project uses seeders only for development bootstrap. Production onboarding needs auditable, repeatable actions performed from the application itself.

**Alternatives considered**:

- Continue using seeds and manual database changes.
  Rejected because it does not satisfy operational onboarding or auditability.
- Allow self-service tenant creation.
  Rejected because the feature is explicitly limited to super-admins.

## Decision 2: Reuse the existing tenant-scoped user model

**Decision**: Keep `users` tied to a single tenant through `tenant_id` and provision new users by attaching them directly to the target tenant.

**Rationale**: The current auth and data-access layers already treat tenant context as single-active-tenant per session. Reusing that model avoids introducing a broader multi-tenant identity system that is not required by the feature.

**Alternatives considered**:

- Introduce a cross-tenant user identity model.
  Rejected because it would expand scope and require a new authorization and session model.
- Store tenant membership only in a separate join table.
  Rejected because the existing codebase already depends on `users.tenant_id` as the canonical tenant scope.

## Decision 3: Add a tenant overview contract for operational verification

**Decision**: Include a super-admin tenant listing and tenant detail response that expose tenant status and membership summary.

**Rationale**: The feature requires not only creation but also post-provisioning verification. A summary view gives operators a single place to confirm that the tenant and its initial admin are ready.

**Alternatives considered**:

- Skip tenant overview and rely only on create responses.
  Rejected because it makes operational verification harder and does not satisfy the review scenario in the spec.

## Decision 4: Keep tenant creation and initial admin provisioning transactional

**Decision**: Create the tenant and initial admin as one atomic business action.

**Rationale**: A tenant without an admin is incomplete. If either part fails, the operation should not leave behind partially provisioned state.

**Alternatives considered**:

- Create tenant first and admin later.
  Rejected because it creates an unusable intermediate state.

## Decision 5: Continue using the existing audit pattern for tenant actions

**Decision**: Record explicit audit events for tenant creation and each provisioning action, reusing the platform's tenant-scoped audit model.

**Rationale**: Auditability is already a core platform invariant and is required for controlled administrative onboarding.

**Alternatives considered**:

- Track these actions only in UI feedback.
  Rejected because it would not provide traceable operational history.
