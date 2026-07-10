# Feature Specification: Enterprise User Lifecycle

**Feature Branch**: `008-enterprise-user-lifecycle`  
**Created**: 2026-05-21  
**Status**: Draft  
**Input**: User description: "creare lifecycle utenti enterprise: con provisioning admin, reset password, MFA, lockout. Con una sola pagina login con scelta: “Accedi con account aziendale” “Accedi con email e password”"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sign in through one unified access page (Priority: P1)

An end user reaches a single sign-in page that clearly offers two supported
access methods so they can enter the platform with either their company account
or their managed email-and-password account.

**Why this priority**: Without a clear and complete sign-in experience, no
enterprise user can enter the system safely or predictably.

**Independent Test**: Can be fully tested by opening the sign-in page,
selecting either sign-in method, completing a valid authentication flow, and
confirming the user lands in an authorized area with an active session.

**Acceptance Scenarios**:

1. **Given** an unauthenticated user opens the access page, **When** the page
   loads, **Then** the system shows one login page with the options
   "Accedi con account aziendale" and "Accedi con email e password".
2. **Given** a user chooses the company-account option, **When** the
   authentication succeeds, **Then** the system signs the user in and grants
   access according to the user's assigned tenant and role.
3. **Given** a user chooses the email-and-password option, **When** valid
   credentials and required verification factors are provided, **Then** the
   system signs the user in and grants access according to the user's assigned
   tenant and role.

---

### User Story 2 - Manage enterprise users through controlled provisioning (Priority: P2)

An authorized administrator can create, activate, suspend, unlock, and manage
enterprise users so the organization can control who is allowed to access the
platform and under which tenant and role.

**Why this priority**: Official users require a governed lifecycle. Without
administrative provisioning and status control, the platform cannot be operated
as an enterprise system.

**Independent Test**: Can be fully tested by having an authorized
administrator provision a user, assign tenant and role, change the user's
status, and confirm that sign-in permissions follow the configured lifecycle.

**Acceptance Scenarios**:

1. **Given** an authorized administrator manages users, **When** they provision
   a new enterprise user, **Then** the system stores the user with a defined
   access method, tenant assignment, role, and lifecycle status.
2. **Given** a provisioned user is suspended or locked, **When** that user
   attempts to sign in, **Then** the system denies access with a clear message
   and records the outcome.
3. **Given** an administrator reactivates or unlocks a user, **When** the user
   tries again with valid credentials, **Then** the system allows access if all
   required security checks are satisfied.

---

### User Story 3 - Recover and protect account access (Priority: P3)

An enterprise user can recover access through password reset when allowed, and
the organization can rely on MFA and lockout protections to reduce account
misuse and repeated invalid access attempts.

**Why this priority**: Password recovery and abuse protection are required for
real production use, but they build on the provisioning and authentication
foundation defined above.

**Independent Test**: Can be fully tested by forcing repeated failed sign-in
attempts, verifying lockout behavior, completing an allowed password reset
flow, and confirming MFA is required before protected access is granted.

**Acceptance Scenarios**:

1. **Given** a managed email-and-password user forgets their password,
   **When** they complete the reset flow successfully, **Then** the system lets
   them define a new secret and use it on the next sign-in.
2. **Given** a user account requires MFA, **When** the primary credentials are
   accepted, **Then** the system requires the second factor before completing
   sign-in.
3. **Given** repeated failed sign-in attempts occur for the same account,
   **When** the configured threshold is exceeded, **Then** the system locks the
   account for further sign-in attempts until the lockout condition ends or an
   authorized administrator intervenes.

### Edge Cases

- What happens when a user provisioned only for company-account access tries to
  sign in with email and password?
- What happens when a user provisioned only for email-and-password access tries
  to use the company-account option?
- How does the system behave when a password reset is requested for a suspended,
  locked, or inactive account?
- What happens when a user starts one sign-in method and then switches to the
  other before completing authentication?
- How does the system respond when MFA verification expires, is entered
  incorrectly multiple times, or is no longer available to the user?
- What happens when a company-account authentication succeeds but the mapped
  user is not provisioned, is assigned to an inactive tenant, or has no active
  role?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST provide one login page that presents both access
  choices: company account and email-and-password.
- **FR-002**: The system MUST allow sign-in through a company-managed account
  for users who are provisioned for that access method.
- **FR-003**: The system MUST allow sign-in through email and password for
  users who are provisioned for that access method.
- **FR-004**: The system MUST let an authorized administrator provision a new
  enterprise user with identity details, tenant assignment, role, lifecycle
  status, and allowed sign-in method or methods.
- **FR-005**: The system MUST let an authorized administrator activate,
  suspend, deactivate, unlock, and otherwise manage enterprise user access
  status without deleting the user's audit history.
- **FR-006**: The system MUST prevent sign-in for users whose lifecycle status
  does not permit access.
- **FR-007**: The system MUST support password reset for managed
  email-and-password accounts and MUST prevent password reset for accounts that
  are not allowed to use password-based sign-in.
- **FR-008**: The system MUST require MFA for accounts and roles governed by
  the organization's access policy before granting protected access.
- **FR-009**: The system MUST enforce account lockout after repeated failed
  sign-in or verification attempts according to the organization's security
  policy.
- **FR-010**: The system MUST let an authorized administrator view the reason
  for a lockout and unlock an account when policy allows it.
- **FR-011**: The system MUST derive tenant and role access from the
  server-side user record after successful authentication and MUST deny access
  when the authenticated identity has no valid tenant-role assignment.
- **FR-012**: The system MUST maintain clear separation between company-account
  identities and managed password identities while still allowing one unified
  entry page.
- **FR-013**: The system MUST show clear Italian feedback for successful
  progression, invalid credentials, expired reset actions, MFA failures,
  account lockout, and access denial due to lifecycle status.
- **FR-014**: The system MUST record audit events for provisioning, status
  changes, login success, login failure, MFA challenge outcomes, password reset
  initiation and completion, lockout, unlock, and access denial.
- **FR-015**: The system MUST preserve user traceability even when a user is no
  longer allowed to sign in.
- **FR-016**: The system MUST ensure that only authorized administrators can
  manage enterprise-user lifecycle actions for users within their governance
  scope.

### API & Contract Requirements *(mandatory for API-backed features)*

- The feature MUST introduce or update versioned contracts for:
  - retrieving the unified login options and required messages
  - initiating company-account sign-in
  - submitting email-and-password sign-in
  - performing MFA verification
  - initiating password reset
  - completing password reset
  - provisioning enterprise users
  - listing and viewing enterprise users
  - updating lifecycle status and unlock actions
- Each endpoint MUST define request fields, validation rules, success
  responses, and standardized error responses.
- Each endpoint MUST define authentication requirements and authorized roles.
- Administrative user-management endpoints MUST declare tenant-scope and role
  rules explicitly.
- Authentication-related endpoints MUST define how the client learns whether an
  additional verification step, account lockout, or lifecycle denial applies.

### Workflow & State Requirements *(mandatory when entities have lifecycle state)*

- Enterprise-user lifecycle states MUST include at least `provisioned`,
  `active`, `suspended`, `locked`, `deactivated`, and `reset_pending` when
  applicable to the user's access method.
- Allowed transitions MUST include:
  - `provisioned -> active`
  - `active -> suspended`
  - `active -> locked`
  - `locked -> active`
  - `suspended -> active`
  - `active -> deactivated`
  - `active -> reset_pending`
  - `reset_pending -> active`
- The actor responsible for provisioning, activation, suspension, deactivation,
  and unlock transitions is an authorized administrator.
- The actor responsible for completing a password reset is the end user, once a
  valid reset journey has been initiated.
- The actor responsible for lockout may be the system after repeated failed
  access attempts.
- Every lifecycle transition MUST record actor, timestamp, previous state, new
  state, tenant scope, affected user, reason, and outcome.

### UI & Localization Requirements *(mandatory for frontend work)*

- All authentication, provisioning, lifecycle-management, reset, MFA, lockout,
  and status-copy UI text MUST be defined for Italian (`it-IT`).
- The sign-in page MUST present both access methods on one page without forcing
  navigation to separate entry screens before the user chooses a method.
- The sign-in page MUST clearly communicate when a user must complete MFA,
  reset their password, or contact an administrator due to lifecycle status.
- The email-and-password flow MUST provide required-field validation,
  credential-error feedback, loading states, and a clear password-reset entry
  point.
- Administrative user-management screens MUST show current lifecycle status,
  allowed sign-in method, tenant assignment, and role assignment for each user.
- Administrative user-management screens MUST support filtering by lifecycle
  status, tenant, role, and sign-in method when user lists are shown.

### Key Entities *(include if feature involves data)*

- **Enterprise User**: Represents a provisioned person who may access the
  platform, including identity data, allowed sign-in method, tenant scope,
  current lifecycle status, and role assignment.
- **Administrative Provisioning Action**: Represents an authorized management
  action that creates or changes a user's lifecycle, role, tenant assignment,
  unlock state, or access method.
- **Password Reset Journey**: Represents the recovery lifecycle for a managed
  password account, including initiation, validity window, completion, and
  failure outcome.
- **MFA Challenge**: Represents a second-factor verification step required
  after primary authentication and before access is granted.
- **Lockout Record**: Represents a system-enforced or administrator-managed
  restriction caused by repeated failed authentication or verification attempts.
- **Identity Access Mapping**: Represents the relationship between an
  authenticated identity and the tenant-role context required for authorized
  access.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of official users can determine their correct sign-in path
  from the unified login page without visiting a separate authentication page.
- **SC-002**: At least 95% of valid sign-in attempts complete successfully
  within 60 seconds, including any required additional verification step.
- **SC-003**: 100% of suspended, deactivated, locked, or unprovisioned users
  are denied protected access.
- **SC-004**: At least 95% of eligible password-reset journeys complete without
  administrator intervention.
- **SC-005**: 100% of user provisioning, lifecycle changes, password-reset
  completions, MFA outcomes, and lockout or unlock events are traceable in the
  audit history.
- **SC-006**: At least 90% of administrators can provision a new user and
  assign the correct access scope on the first attempt.

## Assumptions

- Self-registration is out of scope; every official user is provisioned through
  an authorized administrative process.
- The platform may support both company-account access and managed
  email-and-password access at the same time for different user populations.
- Password reset applies only to users who are allowed to authenticate with
  email and password.
- The organization already defines which user populations require MFA and how
  administrative authority is assigned.
- One active tenant-role context per user session remains the default scope for
  this feature iteration.

## Compliance Notes *(mandatory)*

- This feature is shaped by the constitution requirement for strict server-side
  tenant isolation, mandatory RBAC, stateless authenticated access, auditable
  security events, and Italian-ready UI behavior.
- The feature adds lifecycle controls beyond the simplest sign-in slice because
  official enterprise use requires governed provisioning, recovery, and abuse
  protection. The simpler alternative of keeping only local login plus demo-user
  management was rejected because it is not sufficient for production use with
  official users.
