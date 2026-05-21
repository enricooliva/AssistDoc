# Research: Enterprise User Lifecycle

## Decision 1: Keep one login page, but expose explicit authentication paths

- **Decision**: Present a single Italian login page that offers both
  "Accedi con account aziendale" and "Accedi con email e password", while the
  backend exposes explicit contracts for starting and completing each path.
- **Rationale**: The user experience requires one entry point, but the security
  and lifecycle rules for company-account and managed-password users are not
  the same. Distinct backend contracts preserve clarity without fragmenting the
  entry UI.
- **Alternatives considered**:
  - Two separate sign-in pages.
    - Rejected because it weakens discoverability and conflicts with the
      requested unified access page.
  - One generic login endpoint with many conditional payload shapes.
    - Rejected because it makes validation, auditing, and client-state handling
      harder to reason about and test.

## Decision 2: Use SSO for company-account users outside local development

- **Decision**: Treat company-account sign-in as the production path for
  enterprise SSO users, with AssistDoc accepting the resulting authenticated
  identity and then deriving tenant-role access from the local provisioned user
  record.
- **Rationale**: The constitution requires SSO outside local development and
  keeps tenant and role scope as server-side responsibilities of AssistDoc.
- **Alternatives considered**:
  - Reuse the local email-and-password login for all official users.
    - Rejected because it conflicts with the SSO rule and weakens enterprise
      identity governance.
  - Trust company-account identity alone as sufficient authorization.
    - Rejected because tenant assignment and platform role scope must remain
      controlled by AssistDoc.

## Decision 3: Limit password reset to managed password accounts

- **Decision**: Support password reset only for users who are provisioned for
  email-and-password access and block reset for company-account-only users.
- **Rationale**: Password ownership belongs to AssistDoc only for managed
  password accounts. Resetting company-account credentials would duplicate or
  conflict with upstream identity-provider controls.
- **Alternatives considered**:
  - Allow password reset for all users.
    - Rejected because it creates an invalid control path for SSO-only users.
  - Remove password reset entirely.
    - Rejected because managed password accounts remain part of the requested
      feature scope.

## Decision 4: Make MFA policy-aware across both access paths

- **Decision**: Model MFA as a required sign-in completion state. For managed
  password users, AssistDoc directly verifies the additional factor. For
  company-account users, AssistDoc records that the second-factor requirement
  has been satisfied through the trusted enterprise authentication path or
  denies session completion when policy is not satisfied.
- **Rationale**: The feature requires MFA as part of the lifecycle, but SSO and
  local-password flows cannot be treated identically. A policy-aware MFA state
  keeps the behavior explicit without violating the SSO boundary.
- **Alternatives considered**:
  - Apply only local MFA to managed password accounts.
    - Rejected because it would leave company-account users outside the feature
      scope for MFA governance.
  - Force AssistDoc to challenge company-account users with a second local MFA.
    - Rejected because it duplicates enterprise identity controls and adds
      unnecessary friction.

## Decision 5: Scope lockout to the authentication steps AssistDoc directly controls

- **Decision**: Enforce lockout after repeated failed password sign-in attempts
  and repeated failed platform MFA verification attempts, while treating
  company-account primary-auth lockout as an upstream identity-provider concern.
- **Rationale**: AssistDoc can reliably and audibly govern only the steps it
  directly validates. Applying local lockout to upstream SSO failures would be
  incomplete and potentially inconsistent with the identity provider.
- **Alternatives considered**:
  - No local lockout support.
    - Rejected because it would fail the enterprise-lifecycle requirement.
  - Global lockout regardless of authentication path.
    - Rejected because AssistDoc does not fully own company-account primary
      authentication failure signals.

## Decision 6: Represent lifecycle, reset, MFA, and lockout as separate auditable entities

- **Decision**: Keep the base user record for identity and current access scope,
  and add dedicated records for password reset journeys, MFA challenges, and
  lockout history.
- **Rationale**: The specification requires traceability across provisioning,
  recovery, verification, and denial events. Dedicated entities make the state
  machine testable and auditable.
- **Alternatives considered**:
  - Store all lifecycle and security flags directly on the user row.
    - Rejected because it obscures transition history and weakens auditability.
  - Rely on audit events only with no domain records.
    - Rejected because active security and recovery flows need current-state
      lookup, not just historical logs.
