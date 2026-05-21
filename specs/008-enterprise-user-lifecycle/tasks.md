# Tasks: Enterprise User Lifecycle

**Input**: Design documents from `/specs/008-enterprise-user-lifecycle/`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/auth-enterprise-openapi.yaml`, `quickstart.md`

**Tests**: Tests are mandatory. Write failing tests before implementation tasks for each phase and story.

**Organization**: Tasks are grouped by user story so each story can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel when the referenced files do not overlap and phase dependencies are satisfied
- **[Story]**: User story label for traceability (`[US1]`, `[US2]`, `[US3]`)
- Every task includes an exact file path

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Prepare the enterprise auth design surface, test entry points, and API contract scaffolding

- [X] T001 Validate the enterprise-auth scope against `specs/008-enterprise-user-lifecycle/spec.md` and `specs/008-enterprise-user-lifecycle/contracts/auth-enterprise-openapi.yaml`
- [X] T002 [P] Add backend feature test scaffolding for unified login, enterprise-user provisioning, and recovery flows in `backend/tests/Feature/Auth/UnifiedLoginTest.php`, `backend/tests/Feature/Auth/EnterpriseUserManagementTest.php`, and `backend/tests/Feature/Auth/PasswordRecoveryTest.php`
- [X] T003 [P] Add frontend component and E2E scaffolding for unified login and enterprise-user lifecycle flows in `frontend/src/app/features/auth/sign-in-page.component.spec.ts`, `frontend/src/app/features/users/user-management-page.component.spec.ts`, and `frontend/tests/e2e/enterprise-user-lifecycle.spec.ts`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Introduce shared persistence, auth orchestration boundaries, and validation primitives needed by all stories

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T004 Create enterprise lifecycle persistence migrations for user access methods, password reset journeys, MFA challenges, and lockout records in `backend/database/migrations/2026_05_21_000003_add_enterprise_user_lifecycle_tables.php`
- [X] T005 [P] Extend shared user and auth-related models for lifecycle state, access methods, MFA, reset, and lockout metadata in `backend/app/Models/User.php`, `backend/app/Models/RoleAssignment.php`, `backend/app/Models/PasswordResetJourney.php`, `backend/app/Models/MfaChallenge.php`, and `backend/app/Models/LockoutRecord.php`
- [X] T006 [P] Add shared repositories for enterprise-user lookup, lifecycle state changes, reset journeys, MFA challenges, and lockout tracking in `backend/app/Repositories/UserRepository.php`, `backend/app/Repositories/RoleAssignmentRepository.php`, `backend/app/Repositories/PasswordResetJourneyRepository.php`, `backend/app/Repositories/MfaChallengeRepository.php`, and `backend/app/Repositories/LockoutRecordRepository.php`
- [X] T007 [P] Add validated request objects for unified login, MFA verification, password reset, enterprise-user indexing, creation, status updates, and unlock actions in `backend/app/Http/Requests/LoginOptionsRequest.php`, `backend/app/Http/Requests/PasswordLoginRequest.php`, `backend/app/Http/Requests/MfaVerifyRequest.php`, `backend/app/Http/Requests/PasswordResetStartRequest.php`, `backend/app/Http/Requests/PasswordResetCompleteRequest.php`, `backend/app/Http/Requests/EnterpriseUserIndexRequest.php`, `backend/app/Http/Requests/EnterpriseUserStoreRequest.php`, and `backend/app/Http/Requests/EnterpriseUserStatusUpdateRequest.php`
- [X] T008 [P] Add shared auth service boundaries for company-account start, password login, MFA completion, password reset, and lifecycle denial handling in `backend/app/Services/Auth/AuthService.php`, `backend/app/Services/Auth/EnterpriseUserLifecycleService.php`, and `backend/app/Services/Auth/AuthorizationService.php`

**Checkpoint**: Foundation ready. User stories can now proceed in priority order or in parallel if staffed.

---

## Phase 3: User Story 1 - Sign in through one unified access page (Priority: P1) 🎯 MVP

**Goal**: Let official users authenticate from one page using either company-account access or managed email-and-password access with clear action-required handling

**Independent Test**: Open the sign-in page, choose either login method, complete a valid flow, and verify the user lands in the authorized area or receives the correct MFA/reset/action-required state in Italian

### Tests for User Story 1

- [X] T009 [P] [US1] Add backend feature coverage for login options, company-account start, password login, and current-user restoration in `backend/tests/Feature/Auth/UnifiedLoginTest.php`
- [X] T010 [P] [US1] Add authorization and tenant-scope coverage for unified authentication outcomes in `backend/tests/Feature/Auth/AuthorizationTest.php` and `backend/tests/Feature/Auth/TenantIsolationTest.php`
- [X] T011 [P] [US1] Add frontend component coverage for the unified sign-in page, method selection, action-required states, and Italian feedback in `frontend/src/app/features/auth/sign-in-page.component.spec.ts` and `frontend/src/app/core/auth/auth.service.spec.ts`
- [X] T012 [P] [US1] Add Playwright coverage for the unified login journey in `frontend/tests/e2e/enterprise-user-lifecycle.spec.ts`

### Implementation for User Story 1

- [X] T013 [US1] Implement unified login option retrieval, company-account start orchestration, password login branching, and current-user response mapping in `backend/app/Services/Auth/AuthService.php` and `backend/app/Http/Controllers/Api/V1/AuthController.php`
- [X] T014 [US1] Wire the new auth endpoints, validation, and role-free entry rules in `backend/routes/api.php` and `backend/app/Http/Controllers/Api/V1/AuthController.php`
- [X] T015 [US1] Extend frontend auth models and API client state for login options, action-required responses, and session restoration in `frontend/src/app/core/auth/auth.models.ts` and `frontend/src/app/core/auth/auth.service.ts`
- [X] T016 [US1] Rework the unified sign-in experience with both access choices, password entry, Italian loading/error states, and action-required UX in `frontend/src/app/features/auth/sign-in-page.component.ts`

**Checkpoint**: User Story 1 should be independently functional and testable as the MVP.

---

## Phase 4: User Story 2 - Manage enterprise users through controlled provisioning (Priority: P2)

**Goal**: Let authorized administrators provision and manage official users, tenant-role scope, allowed access methods, and lifecycle status

**Independent Test**: Sign in as an authorized administrator, create a new user with tenant/role/access methods, change lifecycle status, unlock a locked account, and verify the affected user can or cannot sign in accordingly

### Tests for User Story 2

- [X] T017 [P] [US2] Add backend feature coverage for enterprise-user listing, provisioning, status updates, and unlock actions in `backend/tests/Feature/Auth/EnterpriseUserManagementTest.php`
- [X] T018 [P] [US2] Add lifecycle transition and audit coverage for admin-driven provisioning and status management in `backend/tests/Unit/Auth/EnterpriseUserLifecycleServiceTest.php` and `backend/tests/Feature/Auth/AuthorizationTest.php`
- [X] T019 [P] [US2] Add frontend component coverage for enterprise-user list filters, provisioning form, status controls, and unlock actions in `frontend/src/app/features/users/user-management-page.component.spec.ts`
- [X] T020 [P] [US2] Add Playwright coverage for enterprise-user provisioning and lifecycle management in `frontend/tests/e2e/enterprise-user-lifecycle.spec.ts`

### Implementation for User Story 2

- [X] T021 [US2] Implement enterprise-user listing, provisioning, status transition, and unlock orchestration with audit logging in `backend/app/Services/Auth/EnterpriseUserLifecycleService.php`, `backend/app/Repositories/UserRepository.php`, and `backend/app/Repositories/LockoutRecordRepository.php`
- [X] T022 [US2] Expose enterprise-user management endpoints, validation, and admin authorization mapping in `backend/app/Http/Controllers/Api/V1/UserController.php` and `backend/routes/api.php`
- [X] T023 [US2] Extend frontend user-management models and API client methods for listing, provisioning, status updates, and unlock actions in `frontend/src/app/features/users/user-management.models.ts` and `frontend/src/app/features/users/user-management-api.service.ts`
- [X] T024 [US2] Implement the enterprise-user administration UI with filters, provisioning form, lifecycle actions, and Italian feedback in `frontend/src/app/features/users/user-management-page.component.ts`

**Checkpoint**: User Story 2 should be independently functional and testable on top of the US1 auth surface.

---

## Phase 5: User Story 3 - Recover and protect account access (Priority: P3)

**Goal**: Let managed-password users recover access and enforce MFA plus lockout protections with clear administrator and user outcomes

**Independent Test**: Trigger password reset for a managed account, complete the reset, require MFA on sign-in, force repeated failures to produce lockout, and verify administrator unlock restores access

### Tests for User Story 3

- [X] T025 [P] [US3] Add backend feature coverage for password reset initiation/completion, MFA verification, and lockout enforcement in `backend/tests/Feature/Auth/PasswordRecoveryTest.php` and `backend/tests/Feature/Auth/UnifiedLoginTest.php`
- [X] T026 [P] [US3] Add backend unit coverage for reset lifecycle, MFA challenge transitions, and lockout threshold handling in `backend/tests/Unit/Auth/PasswordResetServiceTest.php` and `backend/tests/Unit/Auth/MfaLockoutServiceTest.php`
- [X] T027 [P] [US3] Add frontend component coverage for reset, MFA challenge, and lockout messaging in `frontend/src/app/features/auth/sign-in-page.component.spec.ts` and `frontend/src/app/features/auth/password-reset-page.component.spec.ts`
- [X] T028 [P] [US3] Add Playwright coverage for reset, MFA, and lockout recovery flows in `frontend/tests/e2e/enterprise-user-lifecycle.spec.ts`

### Implementation for User Story 3

- [X] T029 [US3] Implement password reset initiation/completion, MFA challenge verification, and lockout threshold orchestration in `backend/app/Services/Auth/AuthService.php`, `backend/app/Services/Auth/PasswordResetService.php`, and `backend/app/Services/Auth/MfaLockoutService.php`
- [X] T030 [US3] Wire reset and MFA endpoints plus standardized action-required and lockout responses in `backend/app/Http/Controllers/Api/V1/AuthController.php` and `backend/routes/api.php`
- [X] T031 [US3] Extend frontend auth models and client flows for password reset, MFA verification, and lockout state handling in `frontend/src/app/core/auth/auth.models.ts` and `frontend/src/app/core/auth/auth.service.ts`
- [X] T032 [US3] Implement password reset and MFA completion UI, including lockout messaging in `frontend/src/app/features/auth/password-reset-page.component.ts` and `frontend/src/app/features/auth/sign-in-page.component.ts`

**Checkpoint**: All user stories should now be independently functional and testable.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final verification and consistency across auth contracts, auditability, and Italian UX

- [X] T033 [P] Update API route and auth coverage for enterprise-user lifecycle endpoints in `backend/tests/Feature/ApiRoutesTest.php` and `backend/tests/Feature/Auth/CurrentUserTest.php`
- [X] T034 [P] Verify Italian copy, access-denial UX, and session-expiry messaging across the auth and user-management surfaces in `frontend/src/app/features/auth/sign-in-page.component.ts`, `frontend/src/app/features/users/user-management-page.component.ts`, and `frontend/src/assets/i18n/it-IT.json`
- [X] T035 Run and document the enterprise-user lifecycle verification flow in `specs/008-enterprise-user-lifecycle/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies; can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion; blocks all user stories
- **User Story 1 (Phase 3)**: Depends on Foundational completion
- **User Story 2 (Phase 4)**: Depends on Foundational completion and uses the auth surface delivered in US1
- **User Story 3 (Phase 5)**: Depends on Foundational completion and builds on the auth/session flows from US1
- **Polish (Phase 6)**: Depends on the user stories selected for delivery

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Phase 2 and is the MVP slice
- **User Story 2 (P2)**: Starts after Phase 2 but should follow US1 because admin-managed lifecycle outcomes must be visible in the unified login flow
- **User Story 3 (P3)**: Starts after Phase 2 and should follow US1 because reset, MFA, and lockout are sign-in completion behaviors

### Within Each User Story

- Tests must be written and fail before implementation
- Backend orchestration and repository work must land before controller wiring
- API/client models must land before final frontend component behavior
- Lifecycle and audit rules must remain service-owned

### Parallel Opportunities

- T002 and T003 can run in parallel during setup
- T005, T006, T007, and T008 can run in parallel after the migration shape is defined
- In US1, T009 through T012 can run in parallel, then T015 and T016 can run in parallel after T013 and T014 land
- In US2, T017 through T020 can run in parallel, then T023 and T024 can run in parallel after T021 and T022 stabilize the admin lifecycle semantics
- In US3, T025 through T028 can run in parallel, then T031 and T032 can run in parallel after T029 and T030 stabilize reset/MFA/lockout semantics
- T033 and T034 can run in parallel during polish

---

## Parallel Example: User Story 1

```bash
# Launch the US1 failing tests together
Task: "T009 backend/tests/Feature/Auth/UnifiedLoginTest.php"
Task: "T010 backend/tests/Feature/Auth/AuthorizationTest.php and backend/tests/Feature/Auth/TenantIsolationTest.php"
Task: "T011 frontend/src/app/features/auth/sign-in-page.component.spec.ts and frontend/src/app/core/auth/auth.service.spec.ts"
Task: "T012 frontend/tests/e2e/enterprise-user-lifecycle.spec.ts"

# Launch the US1 frontend model/client work after backend auth semantics stabilize
Task: "T015 frontend/src/app/core/auth/auth.models.ts and frontend/src/app/core/auth/auth.service.ts"
Task: "T016 frontend/src/app/features/auth/sign-in-page.component.ts"
```

---

## Parallel Example: User Story 2

```bash
# Launch the US2 failing tests together
Task: "T017 backend/tests/Feature/Auth/EnterpriseUserManagementTest.php"
Task: "T018 backend/tests/Unit/Auth/EnterpriseUserLifecycleServiceTest.php and backend/tests/Feature/Auth/AuthorizationTest.php"
Task: "T019 frontend/src/app/features/users/user-management-page.component.spec.ts"
Task: "T020 frontend/tests/e2e/enterprise-user-lifecycle.spec.ts"

# Launch the US2 frontend model/client work after backend lifecycle semantics stabilize
Task: "T023 frontend/src/app/features/users/user-management.models.ts and frontend/src/app/features/users/user-management-api.service.ts"
Task: "T024 frontend/src/app/features/users/user-management-page.component.ts"
```

---

## Parallel Example: User Story 3

```bash
# Launch the US3 failing tests together
Task: "T025 backend/tests/Feature/Auth/PasswordRecoveryTest.php and backend/tests/Feature/Auth/UnifiedLoginTest.php"
Task: "T026 backend/tests/Unit/Auth/PasswordResetServiceTest.php and backend/tests/Unit/Auth/MfaLockoutServiceTest.php"
Task: "T027 frontend/src/app/features/auth/sign-in-page.component.spec.ts and frontend/src/app/features/auth/password-reset-page.component.spec.ts"
Task: "T028 frontend/tests/e2e/enterprise-user-lifecycle.spec.ts"

# Launch the US3 frontend model/client work after backend reset and MFA semantics stabilize
Task: "T031 frontend/src/app/core/auth/auth.models.ts and frontend/src/app/core/auth/auth.service.ts"
Task: "T032 frontend/src/app/features/auth/password-reset-page.component.ts and frontend/src/app/features/auth/sign-in-page.component.ts"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. Stop and validate that official users can authenticate from one page with clear company-account and managed-password entry paths

### Incremental Delivery

1. Deliver Setup + Foundational to establish lifecycle persistence, auth orchestration, and validation boundaries
2. Deliver US1 as the unified sign-in surface
3. Deliver US2 to add governed enterprise-user provisioning and lifecycle administration
4. Deliver US3 to add password reset, MFA completion, and lockout protections
5. Finish with polish and quickstart verification

### Parallel Team Strategy

1. One engineer handles backend auth and lifecycle persistence plus service changes
2. One engineer handles frontend auth and user-management UI changes
3. One engineer handles feature, component, and Playwright tests once contracts are stable

---

## Notes

- `[P]` tasks touch different files and can run in parallel once dependencies are satisfied
- Every user story remains independently testable
- Keep tenant-scope and role resolution server-side for every lifecycle and authentication outcome
