---

description: "Task list for Tenant Administration"
---

# Tasks: Tenant Administration

**Input**: Design documents from `/specs/010-tenant-admin/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are mandatory for each user story and must be written before implementation tasks.

**Organization**: Tasks are grouped by user story so each story can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story the task belongs to, e.g. `US1`, `US2`, `US3`
- Include exact file paths in descriptions

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Initialize the tenant administration feature area and shared entry points

- [X] T001 [P] Create backend tenant administration file stubs in `backend/app/Http/Controllers/Api/V1/TenantController.php`, `backend/app/Http/Requests/TenantProvisionRequest.php`, `backend/app/Http/Requests/TenantUserProvisionRequest.php`, `backend/app/Http/Requests/TenantIndexRequest.php`, `backend/app/Services/Tenant/TenantAdministrationService.php`, and `backend/app/Repositories/TenantRepository.php`
- [X] T002 [P] Create frontend tenant administration feature stubs in `frontend/src/app/features/tenants/tenant-admin-page.component.ts`, `frontend/src/app/features/tenants/tenant-admin-api.service.ts`, `frontend/src/app/features/tenants/tenant-admin.models.ts`, and `frontend/src/app/features/tenants/tenant-admin-page.component.spec.ts`
- [X] T003 [P] Add tenant administration route and sidebar placeholders in `frontend/src/app/app.routes.ts` and `frontend/src/app/core/layout/app-shell.component.html`
- [X] T004 [P] Add tenant administration API route placeholder in `backend/routes/api.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared backend and frontend building blocks required by all tenant administration stories

**⚠️ CRITICAL**: No user story work should begin until this phase is complete

- [X] T005 [P] Implement tenant repository base queries and membership summary helpers in `backend/app/Repositories/TenantRepository.php`
- [X] T006 Implement tenant administration service orchestration and transaction helpers in `backend/app/Services/Tenant/TenantAdministrationService.php`
- [X] T007 [P] Implement shared tenant provisioning and tenant-scoped validation rules in `backend/app/Http/Requests/TenantProvisionRequest.php`, `backend/app/Http/Requests/TenantUserProvisionRequest.php`, and `backend/app/Http/Requests/TenantIndexRequest.php`
- [X] T008 Register tenant administration controller actions and super-admin guard wiring in `backend/app/Http/Controllers/Api/V1/TenantController.php` and `backend/routes/api.php`

**Checkpoint**: Tenant administration foundation ready - user stories can now be implemented

---

## Phase 3: User Story 1 - Create Tenant with Initial Admin (Priority: P1) 🎯 MVP

**Goal**: Allow a super-admin to create a new tenant and provision the first tenant administrator in a single flow

**Independent Test**: A super-admin can create a tenant from scratch, the initial admin is created in the same action, and the admin can access only the new tenant

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST and ensure they fail before implementation**

- [X] T009 [P] [US1] Add contract test coverage for tenant creation with initial admin in `backend/tests/Feature/TenantAdministrationCreateTest.php`
- [X] T010 [P] [US1] Add authorization and transaction-failure coverage for tenant creation in `backend/tests/Feature/Auth/TenantAdministrationAuthorizationTest.php`
- [X] T011 [P] [US1] Add Playwright coverage for tenant creation and initial admin setup in `frontend/tests/e2e/tenant-admin.spec.ts`
- [X] T012 [P] [US1] Add component test coverage for the tenant creation form in `frontend/src/app/features/tenants/tenant-admin-page.component.spec.ts`

### Implementation for User Story 1

- [X] T013 [P] [US1] Implement tenant provisioning payload validation in `backend/app/Http/Requests/TenantProvisionRequest.php`
- [X] T014 [US1] Implement tenant creation, initial admin provisioning, audit logging, and transaction handling in `backend/app/Services/Tenant/TenantAdministrationService.php`
- [X] T015 [US1] Implement tenant creation and tenant detail endpoints in `backend/app/Http/Controllers/Api/V1/TenantController.php`
- [X] T016 [P] [US1] Build the tenant creation and initial admin form UI in `frontend/src/app/features/tenants/tenant-admin-page.component.ts`
- [X] T017 [P] [US1] Build the tenant admin API client and models for the create flow in `frontend/src/app/features/tenants/tenant-admin-api.service.ts` and `frontend/src/app/features/tenants/tenant-admin.models.ts`

**Checkpoint**: User Story 1 should now be independently functional and demo-ready

---

## Phase 4: User Story 2 - Add Users to a Tenant (Priority: P2)

**Goal**: Allow a super-admin to add additional users to an existing tenant without seed data

**Independent Test**: A super-admin can add a user to an active tenant and the user appears in the tenant user flow without affecting other tenants

### Tests for User Story 2 ⚠️

- [X] T018 [P] [US2] Add contract test coverage for adding a user to a tenant in `backend/tests/Feature/TenantUserProvisioningTest.php`
- [X] T019 [P] [US2] Add authorization and tenant-scope coverage for user provisioning in `backend/tests/Feature/Auth/TenantUserProvisioningAuthorizationTest.php`
- [X] T020 [P] [US2] Add Playwright coverage for provisioning a second user into an existing tenant in `frontend/tests/e2e/tenant-admin.spec.ts`
- [X] T021 [P] [US2] Add component test coverage for the tenant user provisioning form in `frontend/src/app/features/tenants/tenant-admin-page.component.spec.ts`

### Implementation for User Story 2

- [X] T022 [P] [US2] Implement tenant user provisioning payload validation in `backend/app/Http/Requests/TenantUserProvisionRequest.php`
- [X] T023 [US2] Implement additional-user provisioning, tenant scope checks, and audit logging in `backend/app/Services/Tenant/TenantAdministrationService.php`
- [X] T024 [US2] Expose the tenant user provisioning endpoint in `backend/app/Http/Controllers/Api/V1/TenantController.php` and `backend/routes/api.php`
- [X] T025 [P] [US2] Extend the tenant admin UI for selecting a tenant and adding users in `frontend/src/app/features/tenants/tenant-admin-page.component.ts`
- [X] T026 [P] [US2] Extend the tenant admin API client and models for additional-user provisioning in `frontend/src/app/features/tenants/tenant-admin-api.service.ts` and `frontend/src/app/features/tenants/tenant-admin.models.ts`

**Checkpoint**: User Stories 1 and 2 should both work independently

---

## Phase 5: User Story 3 - Review Tenant Membership Summary (Priority: P3)

**Goal**: Allow a super-admin to review tenant status and membership summary after provisioning

**Independent Test**: A super-admin can open the tenant overview and confirm tenant status, member counts, and the initial admin membership

### Tests for User Story 3 ⚠️

- [X] T027 [P] [US3] Add contract test coverage for tenant listing and membership summary in `backend/tests/Feature/TenantOverviewTest.php`
- [X] T028 [P] [US3] Add authorization and isolation coverage for tenant overview access in `backend/tests/Feature/Auth/TenantOverviewAuthorizationTest.php`
- [X] T029 [P] [US3] Add Playwright coverage for reviewing tenant membership summary in `frontend/tests/e2e/tenant-admin.spec.ts`
- [X] T030 [P] [US3] Add component test coverage for tenant overview rendering in `frontend/src/app/features/tenants/tenant-admin-page.component.spec.ts`

### Implementation for User Story 3

- [X] T031 [P] [US3] Implement tenant summary repository queries in `backend/app/Repositories/TenantRepository.php`
- [X] T032 [US3] Implement tenant listing and detail summary endpoints in `backend/app/Http/Controllers/Api/V1/TenantController.php` and `backend/app/Services/Tenant/TenantAdministrationService.php`
- [X] T033 [P] [US3] Render tenant membership summary and status blocks in `frontend/src/app/features/tenants/tenant-admin-page.component.ts`
- [X] T034 [P] [US3] Add Italian copy and empty/loading states for tenant overview in `frontend/src/assets/i18n/it-IT.json`

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [X] T035 [P] Update onboarding seed data and tenant overview sample coverage in `backend/database/seeders/TenantSeeder.php`, `backend/database/seeders/UserSeeder.php`, and `backend/database/seeders/RoleAssignmentSeeder.php`
- [X] T036 [P] Update tenant administration quickstart and operator notes in `specs/010-tenant-admin/quickstart.md` and `backend/README.md`
- [X] T037 [P] Verify route labels and navigation entry consistency in `frontend/src/app/app.routes.ts` and `frontend/src/app/core/layout/app-shell.component.html`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - blocks all user stories
- **User Stories (Phase 3+)**: Depend on Foundational completion
- **Polish (Phase 6)**: Depends on the relevant user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: No dependencies on other stories after foundation
- **User Story 2 (P2)**: Can start after foundation; may reuse User Story 1 UI and backend scaffolding
- **User Story 3 (P3)**: Can start after foundation; depends on tenant create/provisioning data being available for meaningful verification

### Within Each User Story

- Tests must be written before implementation
- Repository/service logic before controller/UI wiring
- Core flow before validation polish and text refinement
- Each story should be complete before moving to the next priority

---

## Parallel Opportunities

- Setup tasks T001, T002, T003, and T004 can run in parallel because they touch different files.
- Foundational tasks T005 and T007 can run in parallel; T006 and T008 should follow once shared service/controller shape is agreed.
- In User Story 1, tests T009-T012 can run in parallel, and UI tasks T016-T017 can run in parallel after backend endpoints are stable.
- In User Story 2, tests T018-T021 can run in parallel, and UI tasks T025-T026 can run in parallel after backend provisioning is stable.
- In User Story 3, tests T027-T030 can run in parallel, and UI tasks T033-T034 can run in parallel after summary contracts are stable.

---

## Parallel Example: User Story 1

```bash
Task: "Add contract test coverage for tenant creation with initial admin in backend/tests/Feature/TenantAdministrationCreateTest.php"
Task: "Add Playwright coverage for tenant creation and initial admin setup in frontend/tests/e2e/tenant-admin.spec.ts"
Task: "Add component test coverage for the tenant creation form in frontend/src/app/features/tenants/tenant-admin-page.component.spec.ts"
Task: "Add authorization and transaction-failure coverage for tenant creation in backend/tests/Feature/Auth/TenantAdministrationAuthorizationTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Setup and Foundational phases.
2. Complete User Story 1.
3. Stop and validate tenant creation with initial admin end-to-end.
4. Demo or release the MVP if ready.

### Incremental Delivery

1. Build the foundation.
2. Deliver tenant creation with initial admin as the first usable increment.
3. Add additional-user provisioning to expand the tenant onboarding flow.
4. Add tenant overview and membership summary for operational verification.

### Parallel Team Strategy

With multiple contributors:

1. Team completes Setup and Foundational work together.
2. One contributor focuses on User Story 1 backend.
3. One contributor focuses on User Story 1 frontend.
4. Another contributor can prepare User Story 2 tests while User Story 1 implementation stabilizes.
