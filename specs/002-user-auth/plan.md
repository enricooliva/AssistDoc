# Implementation Plan: User Authentication

**Branch**: `002-user-auth` | **Date**: 2026-05-08 | **Spec**: [spec.md](/mnt/c/Workspace/spec-kit/AssistDoc/specs/002-user-auth/spec.md)
**Input**: Feature specification from `/specs/002-user-auth/spec.md` plus planning note: "in the implementation considering tenant isolation"

## Summary

Implement a tenant-aware authentication slice for AssistDoc using the existing
Laravel API and Angular SPA so that users must sign in before entering the
system, receive access only within their tenant boundary and assigned role, and
can sign out cleanly while all auth outcomes remain auditable.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), YAML for API contracts  
**Primary Dependencies**: Laravel API stack, Angular SPA, JWT authentication, Bootstrap, ngx-formly  
**Storage**: Relational database for users, tenants, role assignments, and audit events  
**Testing**: PHPUnit feature tests for API/authz/workflow, Angular unit/component tests, Playwright end-to-end tests, contract validation for OpenAPI  
**Target Platform**: Linux containers via Docker Compose for local development  
**Project Type**: Web application with separated backend and frontend in a monorepo  
**Performance Goals**: 95% of successful sign-ins complete and reach an authorized area within 30 seconds; 100% of unauthorized requests are denied; 95% of sign-outs invalidate the active client session on first attempt  
**Constraints**: Stateless JWT auth only; SSO outside local development; strict server-side tenant isolation on every authenticated request; RBAC with `super-admin`, `operator`, `viewer`; Italian UI text; frontend must consume APIs only  
**Scale/Scope**: Existing registered users across multiple company tenants, one active tenant context per authenticated user in MVP scope, core auth endpoints plus guarded frontend routing and audit coverage

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] Specification exists and contains no unresolved `[NEEDS CLARIFICATION]`.
- [x] Planned scope traces to documented requirements and user stories.
- [x] Architecture preserves REST API-first boundaries and does not bypass APIs.
- [x] Backend design follows `Controller -> Service -> Repository -> Model`.
- [x] Business rules, workflow transitions, and transaction boundaries live in Services.
- [x] Contracts are identified for every API, including schemas, validation, auth, and error formats.
- [x] Test-first coverage is planned before implementation starts: API success/failure, authorization, workflow transition, component, and critical Playwright E2E tests.
- [x] Security design enforces stateless JWT auth, SSO outside local, and RBAC with endpoint-level role declarations.
- [x] Frontend plan preserves Angular SPA module boundaries and Italian (`it-IT`) localization requirements.
- [x] Any added complexity is explicitly justified with a simpler alternative and a rollback strategy.

## Project Structure

### Documentation (this feature)

```text
specs/002-user-auth/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── auth-openapi.yaml
└── tasks.md
```

### Source Code (repository root)

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Repositories/
│   └── Services/
│       ├── Auth/
│       └── Audit/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── tests/
    ├── Feature/
    └── Unit/

frontend/
├── src/app/
│   ├── core/
│   │   ├── auth/
│   │   ├── http/
│   │   ├── layout/
│   │   └── tenant/
│   ├── features/
│   │   └── auth/
│   └── shared/
└── tests/
    └── e2e/
```

**Structure Decision**: Use the existing web-application split across
`backend/` and `frontend/`. Authentication, tenant-context resolution, RBAC,
and audit orchestration stay in backend Services, while the Angular app handles
form UX, guarded navigation, and session-aware rendering through API calls only.

## Phase 0: Research Summary

- Finalize authentication approach: one stateless JWT-based API contract for
  login, logout, and current-user resolution, with local credentials allowed in
  development and SSO retained as the non-local policy boundary.
- Finalize tenant isolation approach: tenant context is derived from the
  authenticated user on the server, never trusted from the client, and applied
  uniformly to auth identity loading, authorization checks, and audit logging.
- Finalize frontend access control behavior: sign-in page in Italian, protected
  route enforcement, invalid-session redirect, and role-aware landing behavior
  based on the authenticated user context.
- Finalize audit scope: successful login, failed login, logout, session-expiry
  denial, and cross-tenant or out-of-role access denial all produce immutable
  audit events.

## Phase 1: Design Outputs

- `research.md` records decisions on JWT flow, tenant isolation, RBAC
  enforcement, audit scope, and UI session handling.
- `data-model.md` defines the auth-related entities, validation rules, and
  lifecycle transitions for tenant-aware user access.
- `contracts/auth-openapi.yaml` defines versioned REST endpoints and schemas for
  login, logout, and authenticated user context retrieval.
- `quickstart.md` documents local setup, manual verification steps, and the
  critical tenant-isolated authentication scenarios to prove before coding.
- Agent context will be refreshed from this plan after the design files are
  written.

## Phase 2 Preview

- Derive tasks that start with failing backend auth and authorization tests,
  tenant-aware middleware/service updates, contract conformance, Angular sign-in
  and route-guard behavior, and Playwright coverage for sign-in, sign-out, and
  unauthorized-access flows.

## Post-Design Constitution Check

- [x] Design preserves REST-only interaction between Angular and Laravel.
- [x] Service layer remains the owner of login orchestration, tenant
  resolution, RBAC checks, audit creation, and session invalidation.
- [x] Contracts cover each planned authentication endpoint with schemas,
  validation, auth, and standardized errors.
- [x] Test-first implementation can begin with failing API, authorization,
  component, and Playwright tests.
- [x] JWT, RBAC, tenant isolation, SSO policy boundaries, and Italian
  localization remain explicit in the design.
- [x] Added tenant-isolation enforcement is necessary for security and does not
  introduce unjustified complexity.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| None | N/A | N/A |
