# Implementation Plan: Enterprise User Lifecycle

**Branch**: `008-enterprise-user-lifecycle` | **Date**: 2026-05-21 | **Spec**: [spec.md](/home/enricooliva/Workspace/AssistDoc/specs/008-enterprise-user-lifecycle/spec.md)
**Input**: Feature specification from `/specs/008-enterprise-user-lifecycle/spec.md`

## Summary

Add an enterprise-grade user lifecycle for AssistDoc with a single login page
that supports both company-account access and managed email-and-password
access, plus administrator-driven provisioning, password reset, MFA-aware
sign-in completion, and account lockout controls while preserving strict
tenant-role enforcement and auditability.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12), TypeScript 5.x (Angular SPA; current frontend package is Angular 18)  
**Primary Dependencies**: Laravel API stack, Angular SPA, JWT auth, Bootstrap, ngx-formly, existing `AuthService`, `AuthorizationService`, `TenantContextService`, audit infrastructure  
**Storage**: PostgreSQL-style relational storage for users, tenants, role assignments, audit events, password reset journeys, MFA challenges, and lockout records  
**Testing**: PHPUnit feature/unit tests, Angular component tests, Playwright E2E tests, versioned auth contract validation  
**Target Platform**: Browser-based web application with Laravel API backend  
**Project Type**: web application  
**Performance Goals**: 95% of valid sign-in attempts complete within 60 seconds including required MFA; administrative provisioning updates complete within 5 seconds for normal tenant volumes  
**Constraints**: SSO is mandatory outside local development; JWT remains the session token; tenant isolation is mandatory; all UI text must remain Italian; local password flows are limited to managed accounts; lifecycle transitions and access denials must be auditable  
**Scale/Scope**: Enterprise user administration and authentication lifecycle for official users across multiple tenants; includes unified login, admin provisioning, password reset, MFA completion, and lockout governance; excludes self-registration and broad identity-provider lifecycle management outside AssistDoc

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- Specification exists and contains no unresolved `[NEEDS CLARIFICATION]`.
- Planned scope traces to documented requirements and user stories.
- Architecture preserves REST API-first boundaries and does not bypass APIs.
- Backend design follows `Controller -> Service -> Repository -> Model`.
- Business rules, workflow transitions, and transaction boundaries live in Services.
- Contracts are identified for every API, including schemas, validation, auth, and error formats.
- Test-first coverage is planned before implementation starts: API success/failure, authorization, workflow transition, component, and critical Playwright E2E tests as applicable.
- Security design enforces stateless JWT auth, SSO outside local, and RBAC with endpoint-level role declarations.
- Frontend plan preserves Angular SPA module boundaries and Italian (`it-IT`) localization requirements.
- Any added complexity is explicitly justified with a simpler alternative and a rollback strategy.

## Project Structure

### Documentation (this feature)

```text
specs/008-enterprise-user-lifecycle/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
└── tasks.md
```

### Source Code (repository root)

```text
backend/
├── app/
│   ├── Http/Controllers/Api/V1/AuthController.php
│   ├── Http/Middleware/
│   ├── Http/Requests/
│   ├── Models/
│   ├── Repositories/
│   └── Services/Auth/
├── database/
│   ├── migrations/
│   └── seeders/
└── tests/
    ├── Feature/Auth/
    └── Unit/

frontend/
├── src/app/core/auth/
├── src/app/core/layout/
├── src/app/features/auth/
├── src/app/features/users/
└── tests/e2e/
```

**Structure Decision**: Extend the existing Laravel authentication slice and
Angular auth flow rather than introducing a separate identity service. Backend
changes will remain inside the current auth, user, tenant, role, and audit
boundaries, while the frontend will add a unified sign-in experience plus
administrative user-management screens inside the Angular SPA.

## Phase 0: Research Summary

- Keep one unified login page, but split the backend contract into explicit
  start/complete steps for company-account and managed-password flows.
- Treat company-account access as SSO-governed outside local development, with
  local password authentication remaining available only for managed accounts.
- Use platform-managed MFA completion for the flows AssistDoc directly governs,
  while preserving policy awareness for company-account users authenticated by
  the external identity provider.
- Scope password reset and lockout behavior to managed password accounts and
  platform MFA verification attempts to avoid conflicting with upstream IdP
  controls.

## Phase 1: Design Outputs

- `research.md` records the dual-access, MFA, lockout, and administrative
  lifecycle decisions with rationale and rejected alternatives.
- `data-model.md` defines enterprise-user lifecycle entities, state
  transitions, validation rules, and tenant-role mappings.
- `contracts/auth-enterprise-openapi.yaml` defines the versioned authentication
  and enterprise-user management contract additions for unified login,
  provisioning, reset, MFA, and unlock flows.
- `quickstart.md` documents manual and automated verification for the unified
  login page, provisioning lifecycle, password reset, MFA, and lockout.
- Agent context will be refreshed from this plan after the design files are
  written.

## Phase 2 Preview

- Start with failing backend tests for unified login, enterprise-user
  provisioning, lifecycle denial, password reset, MFA completion, and lockout.
- Add contract coverage and authorization tests for admin-only lifecycle
  actions and tenant-scope restrictions.
- Implement the single-login Angular page, lifecycle-management screens, and
  Playwright coverage for the critical official-user flows.

## Post-Design Constitution Check

- [x] Design preserves REST-only interaction between Angular and Laravel.
- [x] Service layer remains the owner of provisioning, authentication
  orchestration, lifecycle transitions, lockout logic, and audit creation.
- [x] Contracts cover the planned authentication and administrative endpoints
  with schemas, validation, auth, and standardized errors.
- [x] Test-first implementation can begin with failing API, workflow,
  component, and Playwright tests.
- [x] JWT, SSO-outside-local, RBAC, tenant isolation, and Italian localization
  remain explicit in the design.
- [x] Added lifecycle complexity is justified by the requirements for official
  users and has a clear simpler alternative that was rejected.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| Dual access modes on one login page | Official users may belong to different identity populations, but the UX requires one entry point | A single-mode login would exclude either company-account users or managed-password users and would not satisfy the specification |
| Separate lifecycle records for password reset, MFA, and lockout | Security-sensitive transitions need explicit auditability and state control beyond the base user row | Packing all lifecycle state into the user row would obscure transitions, weaken auditing, and make policy enforcement brittle |
