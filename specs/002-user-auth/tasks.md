# Tasks: User Authentication

**Input**: Design documents from `/specs/002-user-auth/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are MANDATORY. Generate test tasks before implementation tasks
for every applicable story and shared foundation.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: backend code in `backend/`, frontend code in `frontend/`
- **Backend tests**: `backend/tests/Feature/`, `backend/tests/Unit/`
- **Frontend tests**: `frontend/src/app/**/*.spec.ts`, `frontend/tests/e2e/`
- **Contracts**: `specs/002-user-auth/contracts/`

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Prepare contracts, test targets, and auth feature scaffolding for tenant-aware implementation

- [X] T001 Create auth feature test files in `backend/tests/Feature/Auth/LoginTest.php`, `backend/tests/Feature/Auth/LogoutTest.php`, `backend/tests/Feature/Auth/CurrentUserTest.php`, `backend/tests/Feature/Auth/TenantIsolationTest.php`, and `backend/tests/Feature/Auth/AuthorizationTest.php`
- [X] T002 [P] Create frontend auth and routing test files in `frontend/src/app/features/auth/sign-in-page.component.spec.ts`, `frontend/src/app/core/auth/auth.service.spec.ts`, `frontend/src/app/core/auth/auth.guard.spec.ts`, and `frontend/tests/e2e/authentication.spec.ts`
- [X] T003 [P] Align the auth contract file in `specs/002-user-auth/contracts/auth-openapi.yaml` with endpoint names, schemas, role rules, and standardized error payloads expected by implementation tests
- [X] T004 [P] Add auth verification steps to `specs/002-user-auth/quickstart.md` for tenant isolation, invalid-session handling, and sign-out regression checks

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core auth, tenant, and audit infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T005 Write failing shared auth API tests in `backend/tests/Feature/Auth/LoginTest.php`, `backend/tests/Feature/Auth/LogoutTest.php`, and `backend/tests/Feature/Auth/CurrentUserTest.php` covering contract compliance and unauthenticated failures
- [X] T006 [P] Write failing shared tenant and RBAC tests in `backend/tests/Feature/Auth/TenantIsolationTest.php` and `backend/tests/Feature/Auth/AuthorizationTest.php` covering cross-tenant denial and role-enforcement behavior
- [X] T007 [P] Write failing shared frontend guard/bootstrap tests in `frontend/src/app/core/auth/auth.guard.spec.ts` and `frontend/src/app/core/auth/auth.service.spec.ts` covering token bootstrap, invalid-session reset, and protected-route redirect behavior
- [X] T008 Update auth and tenant persistence support in `backend/app/Models/User.php`, `backend/app/Models/Tenant.php`, `backend/app/Models/RoleAssignment.php`, `backend/database/migrations/2026_05_08_000001_create_assistdoc_core_tables.php`, and `backend/database/seeders/{TenantSeeder.php,UserSeeder.php,RoleAssignmentSeeder.php}` for deterministic auth and tenant-isolation test data
- [X] T009 [P] Implement tenant-aware auth repository access in `backend/app/Repositories/ChatConversationRepository.php`, `backend/app/Repositories/DocumentRepository.php`, `backend/app/Repositories/SearchQueryRepository.php`, and add `backend/app/Repositories/UserRepository.php` if needed for tenant-scoped user lookup
- [X] T010 [P] Implement centralized tenant context and authorization plumbing in `backend/app/Services/Auth/TenantContextService.php`, `backend/app/Services/Auth/AuthorizationService.php`, `backend/app/Http/Middleware/AuthenticateToken.php`, and `backend/app/Http/Middleware/AuthorizeRole.php`
- [X] T011 Implement authentication orchestration and audit hooks in `backend/app/Services/Auth/AuthService.php` and `backend/app/Services/Audit/AuditService.php` for login, logout, session denial, and role denial outcomes
- [X] T012 [P] Register auth routes and request validation in `backend/routes/api.php`, `backend/app/Http/Requests/ChatMessageRequest.php`, `backend/app/Http/Requests/SearchQueryRequest.php`, and add dedicated auth request classes under `backend/app/Http/Requests/`
- [X] T013 [P] Update frontend auth state plumbing in `frontend/src/app/core/auth/auth.service.ts`, `frontend/src/app/core/http/auth.interceptor.ts`, and `frontend/src/app/core/tenant/tenant-context.service.ts` to bootstrap and clear tenant-aware user context

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Sign in to access the platform (Priority: P1) 🎯 MVP

**Goal**: Let a valid user sign in and enter only the authenticated area allowed for their tenant and role

**Independent Test**: Submit valid credentials, receive authenticated user context, land on an authorized area, and keep access after refresh without exposing other-tenant data

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T014 [P] [US1] Add login success and response-schema tests in `backend/tests/Feature/Auth/LoginTest.php`
- [X] T015 [P] [US1] Add current-user tenant-context tests in `backend/tests/Feature/Auth/CurrentUserTest.php`
- [X] T016 [P] [US1] Add sign-in page UI tests in `frontend/src/app/features/auth/sign-in-page.component.spec.ts`
- [X] T017 [P] [US1] Add successful sign-in E2E flow in `frontend/tests/e2e/authentication.spec.ts`

### Implementation for User Story 1

- [X] T018 [P] [US1] Implement login and current-user endpoints in `backend/app/Http/Controllers/Api/V1/AuthController.php`
- [X] T019 [P] [US1] Add dedicated auth request validation in `backend/app/Http/Requests/LoginRequest.php` and `backend/app/Http/Requests/CurrentUserRequest.php`
- [X] T020 [US1] Implement tenant-aware credential validation, token issuance, and current-user context assembly in `backend/app/Services/Auth/AuthService.php`
- [X] T021 [US1] Persist login audit outcomes and last-login updates in `backend/app/Services/Audit/AuditService.php`, `backend/app/Models/AuditEvent.php`, and `backend/app/Models/User.php`
- [X] T022 [P] [US1] Implement sign-in UI, Italian validation messages, and loading/error states in `frontend/src/app/features/auth/sign-in-page.component.ts`
- [X] T023 [P] [US1] Update app routing and authenticated-shell bootstrap in `frontend/src/app/app.routes.ts`, `frontend/src/app/app.component.ts`, and `frontend/src/app/core/layout/app-shell.component.ts`
- [X] T024 [US1] Add Italian auth copy and tenant-aware authenticated context strings in `frontend/src/assets/i18n/it-IT.json`

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Block invalid access attempts (Priority: P2)

**Goal**: Deny invalid, expired, unauthorized, and cross-tenant access attempts without leaking protected content

**Independent Test**: Attempt login with invalid credentials and attempt protected access without valid auth or against another tenant; each attempt must fail with standardized errors and no data leakage

### Tests for User Story 2 ⚠️

- [X] T025 [P] [US2] Add invalid-credential and expired-session API tests in `backend/tests/Feature/Auth/LoginTest.php` and `backend/tests/Feature/Auth/CurrentUserTest.php`
- [X] T026 [P] [US2] Add cross-tenant and out-of-role denial tests in `backend/tests/Feature/Auth/TenantIsolationTest.php` and `backend/tests/Feature/Auth/AuthorizationTest.php`
- [X] T027 [P] [US2] Add frontend protected-route denial and session-expiry tests in `frontend/src/app/core/auth/auth.guard.spec.ts` and `frontend/src/app/core/auth/auth.service.spec.ts`
- [X] T028 [P] [US2] Add invalid-credentials and protected-route redirect E2E coverage in `frontend/tests/e2e/authentication.spec.ts`

### Implementation for User Story 2

- [X] T029 [P] [US2] Standardize unauthenticated and access-denied API error responses in `backend/app/Http/Middleware/AuthenticateToken.php`, `backend/app/Http/Middleware/AuthorizeRole.php`, and `backend/app/Http/Controllers/Controller.php`
- [X] T030 [US2] Enforce tenant-scoped authorization checks in `backend/app/Services/Auth/AuthorizationService.php`, `backend/app/Services/Auth/TenantContextService.php`, and affected repositories under `backend/app/Repositories/`
- [X] T031 [US2] Record failed login, session denial, and role-denial audit events in `backend/app/Services/Audit/AuditService.php` and `backend/app/Models/AuditEvent.php`
- [X] T032 [P] [US2] Implement frontend redirect, error clearing, and unauthorized messaging in `frontend/src/app/core/auth/auth.service.ts`, `frontend/src/app/core/auth/auth.guard.ts`, and `frontend/src/app/core/layout/app-shell.component.html`
- [X] T033 [US2] Add Italian unauthorized and invalid-session messages in `frontend/src/assets/i18n/it-IT.json`

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - End an authenticated session safely (Priority: P3)

**Goal**: Let an authenticated user sign out so the active client session no longer grants protected access

**Independent Test**: Sign in, sign out, then revisit protected routes and API endpoints; both must require a fresh sign-in

### Tests for User Story 3 ⚠️

- [X] T034 [P] [US3] Add logout success and post-logout denial tests in `backend/tests/Feature/Auth/LogoutTest.php`
- [X] T035 [P] [US3] Add frontend sign-out state-reset tests in `frontend/src/app/core/auth/auth.service.spec.ts` and `frontend/src/app/core/layout/app-shell.component.spec.ts`
- [X] T036 [P] [US3] Add sign-out journey E2E coverage in `frontend/tests/e2e/authentication.spec.ts`

### Implementation for User Story 3

- [X] T037 [P] [US3] Implement logout endpoint and response mapping in `backend/app/Http/Controllers/Api/V1/AuthController.php` and `backend/app/Http/Requests/LogoutRequest.php`
- [X] T038 [US3] Implement token invalidation and logout audit flow in `backend/app/Services/Auth/AuthService.php` and `backend/app/Services/Audit/AuditService.php`
- [X] T039 [P] [US3] Add sign-out action and post-logout navigation handling in `frontend/src/app/core/layout/app-shell.component.ts`, `frontend/src/app/core/layout/app-shell.component.html`, and `frontend/src/app/core/auth/auth.service.ts`
- [X] T040 [US3] Add Italian sign-out feedback copy in `frontend/src/assets/i18n/it-IT.json`

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [X] T041 [P] Update backend unit coverage for auth and authorization services in `backend/tests/Unit/AuthServiceTest.php` and `backend/tests/Unit/AuthorizationServiceTest.php`
- [X] T042 [P] Update developer documentation in `README.md` and `backend/README.md` with tenant-aware authentication setup and verification steps
- [X] T043 Run end-to-end quickstart validation from `specs/002-user-auth/quickstart.md` and capture any required adjustments in `specs/002-user-auth/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Foundational and defines the MVP
- **User Story 2 (P2)**: Starts after Foundational and hardens access control around the US1 auth flow
- **User Story 3 (P3)**: Starts after Foundational and depends on the US1 authenticated session flow being present

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Backend validation and service logic before endpoint integration
- Backend auth behavior before frontend shell integration
- Italian text updates before story sign-off

### Parallel Opportunities

- `T002`, `T003`, and `T004` can run in parallel during Setup
- `T006`, `T007`, `T009`, `T010`, `T012`, and `T013` can run in parallel during Foundational once `T005` is written
- `T014` to `T017` can run in parallel for US1
- `T025` to `T028` can run in parallel for US2
- `T034` to `T036` can run in parallel for US3
- `T041` and `T042` can run in parallel in the Polish phase

---

## Parallel Example: User Story 1

```bash
# Launch all User Story 1 tests together:
Task: "Add login success and response-schema tests in backend/tests/Feature/Auth/LoginTest.php"
Task: "Add current-user tenant-context tests in backend/tests/Feature/Auth/CurrentUserTest.php"
Task: "Add sign-in page UI tests in frontend/src/app/features/auth/sign-in-page.component.spec.ts"
Task: "Add successful sign-in E2E flow in frontend/tests/e2e/authentication.spec.ts"

# Launch parallel implementation slices for User Story 1:
Task: "Implement login and current-user endpoints in backend/app/Http/Controllers/Api/V1/AuthController.php"
Task: "Implement sign-in UI, Italian validation messages, and loading/error states in frontend/src/app/features/auth/sign-in-page.component.ts"
```

---

## Parallel Example: User Story 2

```bash
# Launch all User Story 2 tests together:
Task: "Add invalid-credential and expired-session API tests in backend/tests/Feature/Auth/LoginTest.php and backend/tests/Feature/Auth/CurrentUserTest.php"
Task: "Add cross-tenant and out-of-role denial tests in backend/tests/Feature/Auth/TenantIsolationTest.php and backend/tests/Feature/Auth/AuthorizationTest.php"
Task: "Add frontend protected-route denial and session-expiry tests in frontend/src/app/core/auth/auth.guard.spec.ts and frontend/src/app/core/auth/auth.service.spec.ts"
```

---

## Parallel Example: User Story 3

```bash
# Launch all User Story 3 tests together:
Task: "Add logout success and post-logout denial tests in backend/tests/Feature/Auth/LogoutTest.php"
Task: "Add frontend sign-out state-reset tests in frontend/src/app/core/auth/auth.service.spec.ts and frontend/src/app/core/layout/app-shell.component.spec.ts"
Task: "Add sign-out journey E2E coverage in frontend/tests/e2e/authentication.spec.ts"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. Stop and validate the login flow, tenant-aware user context, and refresh persistence

### Incremental Delivery

1. Complete Setup + Foundational
2. Deliver User Story 1 as the first usable authenticated slice
3. Add User Story 2 to harden invalid and cross-tenant access behavior
4. Add User Story 3 to complete the session lifecycle
5. Finish with documentation, unit coverage, and quickstart validation

### Parallel Team Strategy

1. One engineer builds backend auth foundations while another prepares frontend auth tests and shell behavior
2. After Foundational is complete:
   - Engineer A: US1 backend login/current-user flow
   - Engineer B: US1 frontend sign-in and shell bootstrap
3. Once US1 is stable:
   - Engineer A: US2 denial and audit hardening
   - Engineer B: US3 logout UX and session reset

---

## Notes

- [P] tasks target different files and can be split safely
- [US1], [US2], and [US3] map directly to the specification user stories
- Each story remains independently testable from its checkpoint
- Tenant isolation is treated as a server-side invariant, not a client hint
