# Tasks: Admin, Tenant-Admin Access, and Full User Editing

**Input**: Design documents from `/specs/012-admin-tenant-user-edit/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: Tests are MANDATORY. Generate test tasks before implementation tasks for every applicable story and shared foundation.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Shared test scaffolding and reusable helpers for the feature

- [X] T001 [P] Create reusable backend auth fixtures for `super-admin` and `tenant-admin` in `backend/tests/Feature/Support/EnterpriseUserAccessFixtures.php`
- [X] T002 [P] Create reusable Playwright login helpers for tenant-admin and admin flows in `frontend/tests/e2e/support/admin-tenant-user-edit.ts`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core scaffolding that the user stories build on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T003 [P] Add the user edit request/controller scaffold in `backend/app/Http/Requests/EnterpriseUserUpdateRequest.php` and `backend/app/Http/Controllers/Api/V1/UserController.php`
- [X] T004 [P] Align the document entry-point role checks with the current role model in `frontend/src/app/features/documents/document-upload.component.ts` and `frontend/src/app/core/layout/app-shell.component.ts`
- [X] T005 [P] Add shared edit-form API types for the user management flow in `frontend/src/app/features/users/user-management.models.ts` and `frontend/src/app/features/users/user-management-api.service.ts`

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Access Chat and Documents as Admin Roles (Priority: P1) 🎯 MVP

**Goal**: Allow `super-admin` and `tenant-admin` users to open and use chat and documents without authorization errors.

**Independent Test**: Sign in as a `super-admin` or `tenant-admin`, open chat and documents, and confirm the pages are accessible and usable without authorization errors.

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T006 [P] [US1] Add backend authorization coverage for tenant-admin access to chat and documents in `backend/tests/Feature/Auth/AdminTenantAccessTest.php`
- [X] T007 [P] [US1] Add frontend unit coverage for documents access state and shell navigation visibility in `frontend/src/app/features/documents/document-upload.component.spec.ts` and `frontend/src/app/core/layout/app-shell.component.spec.ts`
- [X] T008 [P] [US1] Add Playwright coverage for tenant-admin opening chat and documents in `frontend/tests/e2e/admin-tenant-access.spec.ts`

### Implementation for User Story 1

- [X] T009 [US1] Update chat and document middleware role lists in `backend/routes/api.php` to include `tenant-admin` on the list, view, and upload document paths and on the chat paths that are part of normal use
- [X] T010 [P] [US1] Update the documents upload role gate and notice text in `frontend/src/app/features/documents/document-upload.component.ts` so tenant-admins can use the documents area

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Edit User Records Across Editable Fields (Priority: P2)

**Goal**: Allow authorized administrators to open an existing user and update the full set of administratively editable fields while keeping protected values read-only.

**Independent Test**: Open the user management area as an authorized administrator, edit a user’s available fields, save the changes, and confirm the updated values persist and reload correctly.

### Tests for User Story 2 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T011 [P] [US2] Add contract coverage for `PATCH /api/v1/users/{userId}` in `backend/tests/Contract/EnterpriseUserEditContractTest.php`
- [X] T012 [P] [US2] Add backend feature coverage for loading and patching users through the edit endpoint in `backend/tests/Feature/Auth/EnterpriseUserUpdateTest.php`
- [X] T013 [P] [US2] Add frontend unit coverage for the user edit flow in `frontend/src/app/features/users/user-management-page.component.spec.ts` and `frontend/src/app/features/users/user-management-api.service.spec.ts`
- [X] T014 [P] [US2] Add Playwright coverage for editing a user and confirming persisted values in `frontend/tests/e2e/admin-tenant-user-edit.spec.ts`

### Implementation for User Story 2

- [X] T015 [P] [US2] Implement the `PATCH /api/v1/users/{userId}` validation and controller handling in `backend/app/Http/Requests/EnterpriseUserUpdateRequest.php` and `backend/app/Http/Controllers/Api/V1/UserController.php`
- [X] T016 [US2] Implement the user update workflow, tenant-scope checks, audit event, role reassignment, and field normalization in `backend/app/Services/Auth/EnterpriseUserLifecycleService.php`, `backend/app/Repositories/UserRepository.php`, and `backend/app/Repositories/RoleAssignmentRepository.php`
- [X] T017 [P] [US2] Extend the user-management API models and service with user detail and update calls in `frontend/src/app/features/users/user-management.models.ts` and `frontend/src/app/features/users/user-management-api.service.ts`
- [X] T018 [US2] Add the user edit dialog and row-level edit action in `frontend/src/app/features/users/user-management-page.component.ts` and `frontend/src/app/features/users/user-edit-dialog.component.ts`

**Checkpoint**: At this point, User Story 1 AND User Story 2 should both work independently

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [X] T019 [P] Reconcile Italian success, error, and read-only field messaging across `frontend/src/app/features/users/user-management-page.component.ts`, `frontend/src/app/features/users/user-edit-dialog.component.ts`, and `frontend/src/app/features/documents/document-upload.component.ts`
- [ ] T020 [P] Validate the updated API contract and run the feature smoke path against `specs/012-admin-tenant-user-edit/contracts/admin-tenant-user-edit-openapi.yaml` and `specs/012-admin-tenant-user-edit/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3)
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P2)**: Can start after Foundational (Phase 2) - May integrate with US1 but should be independently testable

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Backend authorization and request validation before service updates
- Service updates before frontend wiring
- Core implementation before integration
- Story complete before moving to next priority

### Parallel Opportunities

- `T001` and `T002` can run in parallel because they touch different test-support files
- `T003`, `T004`, and `T005` can be split across backend and frontend workstreams
- `T006`, `T007`, and `T008` can run in parallel for US1 test coverage
- `T011`, `T012`, `T013`, and `T014` can run in parallel for US2 test coverage
- `T015` and `T017` can run in parallel while backend and frontend align on the contract
- `T018` can start once `T017` is in place, while `T016` finishes backend persistence rules
- `T019` and `T020` can run in parallel during polish

---

## Parallel Example: User Story 1

```bash
Task: "Add backend authorization coverage for tenant-admin access to chat and documents in backend/tests/Feature/Auth/AdminTenantAccessTest.php"
Task: "Add frontend unit coverage for documents access state and shell navigation visibility in frontend/src/app/features/documents/document-upload.component.spec.ts and frontend/src/app/core/layout/app-shell.component.spec.ts"
Task: "Add Playwright coverage for tenant-admin opening chat and documents in frontend/tests/e2e/admin-tenant-access.spec.ts"
```

## Parallel Example: User Story 2

```bash
Task: "Add contract coverage for PATCH /api/v1/users/{userId} in backend/tests/Contract/EnterpriseUserEditContractTest.php"
Task: "Add backend feature coverage for loading and patching users through the edit endpoint in backend/tests/Feature/Auth/EnterpriseUserUpdateTest.php"
Task: "Add frontend unit coverage for the user edit flow in frontend/src/app/features/users/user-management-page.component.spec.ts and frontend/src/app/features/users/user-management-api.service.spec.ts"
Task: "Add Playwright coverage for editing a user and confirming persisted values in frontend/tests/e2e/admin-tenant-user-edit.spec.ts"
```

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → shared scaffolding ready
2. Add User Story 1 → test independently → deploy/demo the access fix
3. Add User Story 2 → test independently → deploy/demo the user-edit workflow
4. Complete Polish → close out copy, contract, and smoke validation

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1
   - Developer B: User Story 2
3. Stories complete and integrate independently

---

## Notes

- `[P]` tasks = different files, no dependencies
- `[Story]` label maps task to specific user story for traceability
