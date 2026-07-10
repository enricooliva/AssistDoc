# Tasks: Administrative User Search and Soft Delete

**Input**: Design documents from `/specs/009-admin-user-search-delete/`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/admin-user-search-delete-openapi.yaml`, `quickstart.md`

**Tests**: Tests are mandatory. Write failing tests before implementation tasks for each phase and story.

**Organization**: Tasks are grouped by user story so each story can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel when the referenced files do not overlap and dependencies are satisfied
- **[Story]**: User story label for traceability (`[US1]`, `[US2]`, `[US3]`)
- Every task includes an exact file path

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Prepare the search/delete scope, contract coverage points, and test scaffolding for the existing admin-user area

- [X] T001 Validate the search and soft-delete scope against `specs/009-admin-user-search-delete/spec.md` and `specs/009-admin-user-search-delete/contracts/admin-user-search-delete-openapi.yaml`
- [X] T002 [P] Add backend feature test scaffolding for enterprise-user search and delete flows in `backend/tests/Feature/Auth/EnterpriseUserManagementSearchTest.php` and `backend/tests/Feature/Auth/EnterpriseUserDeleteTest.php`
- [X] T003 [P] Add frontend component and E2E scaffolding for user search and soft delete in `frontend/src/app/features/users/user-management-page.component.spec.ts` and `frontend/tests/e2e/enterprise-user-lifecycle.spec.ts`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Extend the shared user model, repository filters, and request/contract boundaries needed by all stories

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T004 Create soft-delete persistence support for enterprise users in `backend/database/migrations/2026_05_22_000001_add_soft_delete_to_users_table.php`
- [X] T005 [P] Extend shared user and related models for soft delete metadata and deleter relations in `backend/app/Models/User.php` and `backend/app/Models/AuditEvent.php`
- [X] T006 [P] Extend shared user repository querying and mapping for tenant-scoped search and deleted-user exclusion in `backend/app/Repositories/UserRepository.php`
- [X] T007 [P] Add validated request objects for searched user indexing and user deletion actions in `backend/app/Http/Requests/EnterpriseUserIndexRequest.php` and `backend/app/Http/Requests/DeleteEnterpriseUserRequest.php`
- [X] T008 [P] Add shared lifecycle service boundaries for user search filtering, soft delete orchestration, and repeated-delete handling in `backend/app/Services/Auth/EnterpriseUserLifecycleService.php` and `backend/app/Services/Auth/AuthorizationService.php`

**Checkpoint**: Foundation ready. User stories can now proceed in priority order or in parallel if staffed.

---

## Phase 3: User Story 1 - Search Enterprise Users in the Existing Admin List (Priority: P1) 🎯 MVP

**Goal**: Let a super-admin search the current tenant-scoped user list by name or email while preserving existing pagination semantics

**Independent Test**: Open the `/users` admin page, search by full name or email, verify only matching tenant-scoped users appear, then clear the search and confirm the normal paginated list returns

### Tests for User Story 1

- [X] T009 [P] [US1] Add backend feature coverage for tenant-scoped user search results and empty-search behavior in `backend/tests/Feature/Auth/EnterpriseUserManagementSearchTest.php`
- [X] T010 [P] [US1] Add authorization and tenant-isolation coverage for searched user listing in `backend/tests/Feature/Auth/AuthorizationTest.php` and `backend/tests/Feature/Auth/TenantIsolationTest.php`
- [X] T011 [P] [US1] Add frontend component coverage for search input, empty-state messaging, and list reset behavior in `frontend/src/app/features/users/user-management-page.component.spec.ts`
- [X] T012 [P] [US1] Add Playwright coverage for the admin user search flow in `frontend/tests/e2e/enterprise-user-lifecycle.spec.ts`

### Implementation for User Story 1

- [X] T013 [US1] Implement tenant-scoped search filtering and paginated search response mapping in `backend/app/Repositories/UserRepository.php` and `backend/app/Services/Auth/EnterpriseUserLifecycleService.php`
- [X] T014 [US1] Expose searched list validation and list endpoint updates in `backend/app/Http/Controllers/Api/V1/UserController.php` and `backend/routes/api.php`
- [X] T015 [US1] Extend frontend user-management models and API client for searched pagination requests in `frontend/src/app/features/users/user-management.models.ts` and `frontend/src/app/features/users/user-management-api.service.ts`
- [X] T016 [US1] Implement admin user search UI, clear/reset behavior, and Italian empty-state feedback in `frontend/src/app/features/users/user-management-page.component.ts` and `frontend/src/assets/i18n/it-IT.json`

**Checkpoint**: User Story 1 should be independently functional and testable as the MVP.

---

## Phase 4: User Story 2 - Soft Delete an Enterprise User from the Existing Admin Area (Priority: P2)

**Goal**: Let a super-admin soft delete a user from the current admin area so the user disappears from the standard active list while historical references stay intact

**Independent Test**: Soft delete one listed user from `/users`, confirm the user disappears from the standard list and standard search results, and verify the action remains traceable

### Tests for User Story 2

- [X] T017 [P] [US2] Add backend feature coverage for successful enterprise-user soft delete and post-delete list exclusion in `backend/tests/Feature/Auth/EnterpriseUserDeleteTest.php`
- [X] T018 [P] [US2] Add backend unit coverage for delete orchestration, audit payload, and deleted-user exclusion rules in `backend/tests/Unit/Auth/EnterpriseUserLifecycleServiceSoftDeleteTest.php`
- [X] T019 [P] [US2] Add frontend component coverage for delete confirmation and post-delete list refresh in `frontend/src/app/features/users/user-management-page.component.spec.ts`
- [X] T020 [P] [US2] Add Playwright coverage for the admin user soft-delete journey in `frontend/tests/e2e/enterprise-user-lifecycle.spec.ts`

### Implementation for User Story 2

- [X] T021 [US2] Implement soft delete persistence, deleted-by tracking, and audit logging for enterprise users in `backend/app/Models/User.php`, `backend/app/Repositories/UserRepository.php`, and `backend/app/Services/Auth/EnterpriseUserLifecycleService.php`
- [X] T022 [US2] Expose the soft-delete endpoint, response mapping, and standardized delete outcomes in `backend/app/Http/Controllers/Api/V1/UserController.php`, `backend/app/Http/Requests/DeleteEnterpriseUserRequest.php`, and `backend/routes/api.php`
- [X] T023 [US2] Extend frontend user-management models and API client for delete requests and delete responses in `frontend/src/app/features/users/user-management.models.ts` and `frontend/src/app/features/users/user-management-api.service.ts`
- [X] T024 [US2] Implement delete confirmation UX, success feedback, and list/search refresh after delete in `frontend/src/app/features/users/user-management-page.component.ts` and `frontend/src/assets/i18n/it-IT.json`

**Checkpoint**: User Story 2 should be independently functional and testable on top of the searched list from US1.

---

## Phase 5: User Story 3 - Prevent Unsafe or Incoherent User Deletion (Priority: P3)

**Goal**: Return clear, non-destructive outcomes when deletion is unauthorized, repeated, self-targeted, or blocked by business rules

**Independent Test**: Attempt delete outside governance scope, repeat delete on the same user, and try a blocked delete case; each attempt should leave data unchanged and show a clear Italian outcome

### Tests for User Story 3

- [X] T025 [P] [US3] Add backend feature coverage for repeated delete conflicts, self-delete denial, and out-of-scope delete denial in `backend/tests/Feature/Auth/EnterpriseUserDeleteTest.php` and `backend/tests/Feature/Auth/AuthorizationTest.php`
- [X] T026 [P] [US3] Add backend unit coverage for blocked delete rules and denial audit outcomes in `backend/tests/Unit/Auth/EnterpriseUserLifecycleServiceSoftDeleteTest.php`
- [X] T027 [P] [US3] Add frontend component coverage for denied-delete and repeated-delete feedback states in `frontend/src/app/features/users/user-management-page.component.spec.ts`
- [X] T028 [P] [US3] Add Playwright coverage for denied and repeated admin delete flows in `frontend/tests/e2e/enterprise-user-lifecycle.spec.ts`

### Implementation for User Story 3

- [X] T029 [US3] Implement repeated-delete conflict handling, self-delete denial, and governance-scope delete rules in `backend/app/Services/Auth/EnterpriseUserLifecycleService.php` and `backend/app/Services/Auth/AuthorizationService.php`
- [X] T030 [US3] Wire standardized denial/conflict responses for delete edge cases in `backend/app/Http/Controllers/Api/V1/UserController.php` and `backend/routes/api.php`
- [X] T031 [US3] Extend frontend API error handling for delete conflicts and denial messages in `frontend/src/app/features/users/user-management-api.service.ts`
- [X] T032 [US3] Implement Italian denial/conflict feedback for blocked delete outcomes in `frontend/src/app/features/users/user-management-page.component.ts` and `frontend/src/assets/i18n/it-IT.json`

**Checkpoint**: All user stories should now be independently functional and testable.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final verification across routing, auth, localization, and documented validation flow

- [X] T033 [P] Update route and auth coverage for searched list and soft-delete endpoints in `backend/tests/Feature/ApiRoutesTest.php` and `backend/tests/Feature/Auth/CurrentUserTest.php`
- [X] T034 [P] Verify Italian copy, pagination coherence, and delete-confirmation UX across the user-management surface in `frontend/src/app/features/users/user-management-page.component.ts` and `frontend/src/assets/i18n/it-IT.json`
- [X] T035 Run and document the admin user search and soft-delete verification flow in `specs/009-admin-user-search-delete/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies; can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion; blocks all user stories
- **User Story 1 (Phase 3)**: Depends on Foundational completion
- **User Story 2 (Phase 4)**: Depends on Foundational completion and benefits from the searched list delivered in US1
- **User Story 3 (Phase 5)**: Depends on Foundational completion and the delete semantics established in US2
- **Polish (Phase 6)**: Depends on the user stories selected for delivery

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Phase 2 and is the MVP slice
- **User Story 2 (P2)**: Starts after Phase 2 but should follow US1 because delete refresh behavior builds on the searched list
- **User Story 3 (P3)**: Starts after Phase 2 and should follow US2 because it refines delete-denial and repeated-delete semantics

### Within Each User Story

- Tests must be written and fail before implementation
- Repository and model changes must land before controller response mapping
- API/client model work must land before final frontend behavior
- Audit and governance rules must remain service-owned

### Parallel Opportunities

- T002 and T003 can run in parallel during setup
- T005, T006, T007, and T008 can run in parallel after the migration shape is defined
- In US1, T009 through T012 can run in parallel, then T015 and T016 can run in parallel after T013 and T014 land
- In US2, T017 through T020 can run in parallel, then T023 and T024 can run in parallel after T021 and T022 stabilize delete semantics
- In US3, T025 through T028 can run in parallel, then T031 and T032 can run in parallel after T029 and T030 stabilize denied-delete outcomes
- T033 and T034 can run in parallel during polish

---

## Parallel Example: User Story 1

```bash
# Launch the US1 failing tests together
Task: "T009 backend/tests/Feature/Auth/EnterpriseUserManagementSearchTest.php"
Task: "T010 backend/tests/Feature/Auth/AuthorizationTest.php and backend/tests/Feature/Auth/TenantIsolationTest.php"
Task: "T011 frontend/src/app/features/users/user-management-page.component.spec.ts"
Task: "T012 frontend/tests/e2e/enterprise-user-lifecycle.spec.ts"

# Launch the US1 frontend model/client work after backend search semantics stabilize
Task: "T015 frontend/src/app/features/users/user-management.models.ts and frontend/src/app/features/users/user-management-api.service.ts"
Task: "T016 frontend/src/app/features/users/user-management-page.component.ts and frontend/src/assets/i18n/it-IT.json"
```

---

## Parallel Example: User Story 2

```bash
# Launch the US2 failing tests together
Task: "T017 backend/tests/Feature/Auth/EnterpriseUserDeleteTest.php"
Task: "T018 backend/tests/Unit/Auth/EnterpriseUserLifecycleServiceSoftDeleteTest.php"
Task: "T019 frontend/src/app/features/users/user-management-page.component.spec.ts"
Task: "T020 frontend/tests/e2e/enterprise-user-lifecycle.spec.ts"

# Launch the US2 frontend model/client work after backend delete semantics stabilize
Task: "T023 frontend/src/app/features/users/user-management.models.ts and frontend/src/app/features/users/user-management-api.service.ts"
Task: "T024 frontend/src/app/features/users/user-management-page.component.ts and frontend/src/assets/i18n/it-IT.json"
```

---

## Parallel Example: User Story 3

```bash
# Launch the US3 failing tests together
Task: "T025 backend/tests/Feature/Auth/EnterpriseUserDeleteTest.php and backend/tests/Feature/Auth/AuthorizationTest.php"
Task: "T026 backend/tests/Unit/Auth/EnterpriseUserLifecycleServiceSoftDeleteTest.php"
Task: "T027 frontend/src/app/features/users/user-management-page.component.spec.ts"
Task: "T028 frontend/tests/e2e/enterprise-user-lifecycle.spec.ts"

# Launch the US3 frontend error-state work after backend denial semantics stabilize
Task: "T031 frontend/src/app/features/users/user-management-api.service.ts"
Task: "T032 frontend/src/app/features/users/user-management-page.component.ts and frontend/src/assets/i18n/it-IT.json"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. Stop and validate that a super-admin can search the tenant-scoped user list from the existing admin area

### Incremental Delivery

1. Deliver Setup + Foundational to establish search, soft-delete persistence, and contract boundaries
2. Deliver US1 as the searchable admin-user list
3. Deliver US2 to add governed user soft delete
4. Deliver US3 to harden repeated-delete, self-delete, and out-of-scope denial behavior
5. Finish with polish and quickstart verification

### Parallel Team Strategy

1. One engineer handles backend repository, service, migration, and contract updates
2. One engineer handles frontend user-management search/delete UX changes
3. One engineer handles feature, component, and Playwright tests once contracts stabilize

---

## Notes

- `[P]` tasks touch different files and can run in parallel once dependencies are satisfied
- Every user story remains independently testable
- Keep tenant-scope, delete rules, and audit outcomes server-side for every search and deletion result
