# Implementation Plan: Admin, Tenant-Admin Access, and Full User Editing

**Branch**: `012-admin-tenant-user-edit` | **Date**: 2026-05-25 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/012-admin-tenant-user-edit/spec.md`

## Summary

Extend the existing Laravel API and Angular SPA so `super-admin` and `tenant-admin` users can work in chat and documents without role mismatches, and so authorized administrators can open and update existing users across the full administratively editable field set. The implementation keeps the current shell, tenant scope, and audit model intact, adds a dedicated user update API, and uses Bootstrap/ng-bootstrap patterns rather than introducing a new CSS-heavy layout.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12), TypeScript 5.x (Angular SPA)  
**Primary Dependencies**: Laravel API middleware and controllers, Angular reactive forms, Bootstrap, ng-bootstrap, existing `EnterpriseUserLifecycleService`, existing chat/document services  
**Storage**: Existing PostgreSQL-style relational storage for users, tenants, role assignments, access methods, and audit events; no new tables expected  
**Testing**: PHPUnit feature tests, Angular unit tests, and Playwright E2E for auth and admin workflows  
**Target Platform**: Web application  
**Project Type**: Full-stack web application  
**Performance Goals**: Keep user list and edit flows within the current response-time envelope; no new expensive joins or background jobs are required  
**Constraints**: Preserve JWT auth, tenant scope, current role model (`super-admin`, `tenant-admin`, `operator`, `viewer`), and Italian UI copy; avoid bypassing the API layer  
**Scale/Scope**: One new edit workflow for enterprise users plus role-access adjustments on existing chat and document entry points

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
specs/012-admin-tenant-user-edit/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── admin-tenant-user-edit-openapi.yaml
└── tasks.md
```

### Source Code (repository root)

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/UserController.php
│   │   └── Requests/EnterpriseUser*.php
│   └── Services/Auth/EnterpriseUserLifecycleService.php
└── tests/
    └── Feature/Auth/EnterpriseUserManagementTest.php

frontend/
├── src/app/
│   ├── app.routes.ts
│   ├── core/auth/auth.guard.ts
│   ├── features/chat/
│   ├── features/documents/
│   └── features/users/
└── tests/
    └── e2e/
```

**Structure Decision**: This is a Laravel API plus Angular SPA web application. The work stays inside the existing backend controller/service/request layers and the existing frontend feature modules for chat, documents, and users. No new app shell or standalone admin console is introduced.

## Complexity Tracking

No constitution violations require justification. The feature can be delivered by extending the current API, service, and Angular feature modules.
