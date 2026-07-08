# Research: Admin, Tenant-Admin Access, and Full User Editing

## Decision 1: Map the user-facing "admin" role to the existing `super-admin` role

**Decision**: Treat the spec term `admin` as the platform's existing `super-admin` role.

**Rationale**: The current codebase exposes `super-admin`, `tenant-admin`, `operator`, and `viewer` as the concrete roles. Using `super-admin` avoids inventing a new role and keeps the implementation aligned with the current auth model.

**Alternatives considered**:

- Add a new `admin` role.
  Rejected because it would duplicate `super-admin` semantics and require a broader auth migration.
- Keep the spec wording as-is and add a second alias everywhere.
  Rejected because it would complicate guards, tests, and API middleware without adding functional value.

## Decision 2: Extend the existing chat and document authorization matrix instead of creating a separate admin area

**Decision**: Keep the current chat and documents screens and extend their role checks and backend middleware so `tenant-admin` can use them where it is currently blocked.

**Rationale**: The project already has working chat and document flows. The smallest safe change is to widen the role matrix on the existing entry points rather than introduce duplicate admin-only screens.

**Alternatives considered**:

- Build a separate admin workspace for chat and documents.
  Rejected because it would fragment the navigation model and diverge from the current Angular shell.
- Leave the frontend unchanged and only relax the API.
  Rejected because the UI still needs to expose the entry points and state consistently for the newly authorized role.

## Decision 3: Add a dedicated user update endpoint and keep edits server-driven

**Decision**: Introduce `PATCH /api/v1/users/{userId}` and back it with the existing user lifecycle service, plus a request class that whitelists the administratively editable fields.

**Rationale**: The current user management surface supports create, status update, unlock, and delete, but not full edit. A dedicated patch endpoint keeps the contract explicit and lets the service enforce tenant and role scope centrally.

**Alternatives considered**:

- Overload the create endpoint for edits.
  Rejected because create and update have different validation and lifecycle rules.
- Update users directly from the frontend with multiple existing endpoints.
  Rejected because that would split validation across several requests and make rollback harder.

## Decision 4: Make only administratively editable fields writable and keep system-managed fields read-only

**Decision**: Allow edits for the fields already used by the user lifecycle flow, and keep system-managed values such as lock timestamps, deletion metadata, and audit fields read-only.

**Rationale**: "Modify the user in all fields" is best interpreted as all fields that are safe for administrative editing. Protected fields are controlled by lifecycle or audit logic and should not be mutable from the form.

**Alternatives considered**:

- Make every persisted user column editable.
  Rejected because it would weaken traceability and allow unsafe writes to lifecycle metadata.
- Hide protected fields completely.
  Rejected because read-only visibility is useful for administrators when diagnosing status, lockout, and access issues.

## Decision 5: Keep Bootstrap and ng-bootstrap as the UI extension path

**Decision**: Use Bootstrap utilities and ng-bootstrap components for the edit workflow and any permission-related UI refinements, while preserving the existing Angular feature module structure.

**Rationale**: The repo already uses Bootstrap-based layouts. Staying in that style keeps the change consistent and avoids a custom CSS layer that would be harder to maintain.

**Alternatives considered**:

- Rewrite the user-management UI with bespoke CSS and layout primitives.
  Rejected because it would create unnecessary visual drift from the rest of the app.
- Introduce a new component library.
  Rejected because it would add migration overhead for a focused feature.
