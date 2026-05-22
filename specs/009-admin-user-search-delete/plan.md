# Implementation Plan: Administrative User Search and Soft Delete

**Branch**: `009-admin-user-search-delete` | **Date**: 2026-05-22 | **Spec**: [/home/enricooliva/Workspace/AssistDoc/specs/009-admin-user-search-delete/spec.md](/home/enricooliva/Workspace/AssistDoc/specs/009-admin-user-search-delete/spec.md)
**Input**: Feature specification from `/specs/009-admin-user-search-delete/spec.md`

## Summary

Extend the existing enterprise-user administration flow with tenant-scoped user
search and soft delete. The backend will extend the current `/api/v1/users`
listing contract with query-based filtering and add an authenticated
administrator delete endpoint that performs soft delete on `users` while
preserving lifecycle, audit, and historical references. The frontend will
evolve the existing Angular user-management page with a search input, explicit
soft-delete confirmation, updated empty states, and pagination behavior that
remains coherent with the current implementation.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12), TypeScript 5.x (Angular SPA; current frontend package is Angular 18)  
**Primary Dependencies**: Laravel API stack, Angular SPA, JWT auth, Bootstrap, existing `EnterpriseUserLifecycleService`, existing `UserRepository`, existing audit infrastructure  
**Storage**: PostgreSQL-style relational storage for users, tenants, role assignments, audit events, password reset journeys, MFA challenges, and lockout records  
**Testing**: Laravel feature/unit tests, Angular component tests, Playwright E2E scaffolding  
**Target Platform**: Linux-hosted web application with Laravel backend and Angular SPA
**Project Type**: Web application  
**Performance Goals**: User search returns relevant tenant-scoped results within normal paginated admin-list interaction time and soft delete removes a user from the active list immediately after success  
**Constraints**: Preserve stateless JWT auth, endpoint-level RBAC, Italian UX copy, service-owned business rules, and coherence with the already implemented enterprise-user lifecycle  
**Scale/Scope**: Super-admin user-management surface for tenant-scoped enterprise users, including paginated lists, search refinement, and non-destructive deletion

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- Specification exists and contains no unresolved `[NEEDS CLARIFICATION]`.
- Planned scope traces to documented requirements and user stories.
- Architecture preserves REST API-first boundaries and does not bypass APIs.
- Backend design follows `Controller -> Service -> Repository -> Model`.
- Business rules, workflow transitions, and transaction boundaries live in
  Services.
- Contracts are identified for every API, including schemas, validation, auth,
  and error formats.
- Test-first coverage is planned before implementation starts:
  API success/failure, authorization, workflow transition, component, and
  critical Playwright E2E tests as applicable.
- Security design enforces stateless JWT auth, SSO outside local, and RBAC with
  endpoint-level role declarations.
- Frontend plan preserves Angular SPA module boundaries and Italian (`it-IT`)
  localization requirements.
- Any added complexity is explicitly justified with a simpler alternative and a
  rollback strategy.

**Gate Result**: PASS

## Project Structure

### Documentation (this feature)

```text
specs/009-admin-user-search-delete/
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
│   ├── Http/Controllers/Api/V1/
│   ├── Http/Requests/
│   ├── Models/
│   ├── Repositories/
│   └── Services/Auth/
├── database/
│   └── migrations/
├── routes/
│   └── api.php
└── tests/
    ├── Feature/Auth/
    └── Unit/Auth/

frontend/
├── src/app/
│   ├── core/auth/
│   └── features/users/
└── tests/e2e/
```

**Structure Decision**: Keep the existing Laravel backend and Angular SPA
structure. Extend the current auth/user-management slice rather than
introducing a new module or alternate administrative surface.

## Phase 0: Research Output

See [research.md](/home/enricooliva/Workspace/AssistDoc/specs/009-admin-user-search-delete/research.md) for design decisions on search semantics, user soft delete modeling, repeated-delete behavior, and list/pagination coherence.

## Phase 1: Design Output

- [data-model.md](/home/enricooliva/Workspace/AssistDoc/specs/009-admin-user-search-delete/data-model.md)
- [contracts/admin-user-search-delete-openapi.yaml](/home/enricooliva/Workspace/AssistDoc/specs/009-admin-user-search-delete/contracts/admin-user-search-delete-openapi.yaml)
- [quickstart.md](/home/enricooliva/Workspace/AssistDoc/specs/009-admin-user-search-delete/quickstart.md)

## Post-Design Constitution Check

- REST API-first boundary preserved: search and delete remain API-mediated.
- Layered architecture preserved: controller validation/response mapping,
  service-owned lifecycle and delete rules, repository-owned query/persistence.
- Contract-first requirement satisfied with explicit list and delete contract
  updates.
- Test-first expectation preserved in upcoming task plan: feature, authz,
  tenant isolation, component, and E2E coverage.
- Security and localization constraints preserved: `super-admin` only,
  JWT-authenticated, Italian admin UX.
- Complexity remains minimal: reuse of existing user list/service is simpler
  than introducing a separate deleted-user workflow.

**Gate Result**: PASS

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| None | N/A | N/A |
