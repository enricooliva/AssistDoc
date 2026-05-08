# Tasks: AssistDoc Private Document Assistant

**Input**: Design documents from `/specs/001-private-doc-assistant/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are MANDATORY. Generate test tasks before implementation tasks for every applicable story and shared foundation.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- Backend application lives under `backend/`
- Frontend application lives under `frontend/`
- Infrastructure and container files live under `infra/docker/`

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and baseline workspace scaffolding

- [X] T001 Create backend, frontend, and infra directory skeleton per plan in `backend/`, `frontend/`, and `infra/docker/`
- [X] T002 Initialize Laravel backend application skeleton and Composer configuration in `backend/composer.json`, `backend/artisan`, and `backend/app/`
- [X] T003 [P] Initialize Angular SPA skeleton and package configuration in `frontend/package.json`, `frontend/angular.json`, and `frontend/src/`
- [X] T004 [P] Create Docker Compose baseline for frontend, backend, worker, MySQL, Qdrant, and Ollama in `docker-compose.yml` and `infra/docker/`
- [X] T005 [P] Configure backend test bootstrap for API, workflow, and authorization coverage in `backend/phpunit.xml` and `backend/tests/`
- [X] T006 [P] Configure frontend unit/component and Playwright E2E test bootstrap in `frontend/package.json`, `frontend/playwright.config.ts`, and `frontend/tests/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T007 [P] Add failing backend contract-validation and API smoke tests for auth, documents, search, chat, and audit routes in `backend/tests/Feature/Api/Contract/ApiContractTest.php`
- [X] T008 [P] Add failing frontend app-shell and route-guard tests for authenticated tenant access in `frontend/src/app/core/auth/auth.guard.spec.ts` and `frontend/src/app/core/layout/app-shell.component.spec.ts`
- [X] T009 [P] Create foundational database migrations for tenants, users, role assignments, documents, chat, search, and audit tables in `backend/database/migrations/`
- [X] T010 [P] Implement core Eloquent models for tenant, user, role assignment, and audit entities in `backend/app/Models/Tenant.php`, `backend/app/Models/User.php`, `backend/app/Models/RoleAssignment.php`, and `backend/app/Models/AuditEvent.php`
- [X] T011 Implement JWT authentication, development login flow, and auth middleware in `backend/app/Http/Controllers/Api/V1/AuthController.php`, `backend/app/Services/Auth/AuthService.php`, and `backend/app/Http/Middleware/AuthenticateJwt.php`
- [X] T012 [P] Implement tenant context resolution and RBAC enforcement in `backend/app/Services/Auth/TenantContextService.php`, `backend/app/Services/Auth/AuthorizationService.php`, and `backend/app/Http/Middleware/AuthorizeRole.php`
- [X] T013 [P] Implement standardized API error responses and request validation base classes in `backend/app/Http/Responses/ApiErrorResponse.php` and `backend/app/Http/Requests/`
- [X] T014 [P] Register API v1 routes, middleware groups, and endpoint role declarations in `backend/routes/api.php`
- [X] T015 [P] Implement audit-event recording foundation and service-level helper APIs in `backend/app/Services/Audit/AuditService.php` and `backend/app/Repositories/AuditEventRepository.php`
- [X] T016 [P] Create Angular core auth, tenant, and HTTP interceptor services in `frontend/src/app/core/auth/auth.service.ts`, `frontend/src/app/core/tenant/tenant-context.service.ts`, and `frontend/src/app/core/http/auth.interceptor.ts`
- [X] T017 [P] Create Angular application shell, Bootstrap layout baseline, and Italian localization scaffolding in `frontend/src/app/core/layout/`, `frontend/src/app/app.routes.ts`, and `frontend/src/assets/i18n/it-IT.json`
- [X] T018 Seed development tenant, role, and user fixtures for local verification in `backend/database/seeders/TenantSeeder.php`, `backend/database/seeders/UserSeeder.php`, and `backend/database/seeders/RoleAssignmentSeeder.php`

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Ask Company Knowledge Questions (Priority: P1) 🎯 MVP

**Goal**: Let an authenticated tenant user ask knowledge questions and receive cited answers grounded only in that tenant's indexed documents.

**Independent Test**: Sign in as a seeded tenant user with indexed documents, submit questions from the chat screen, and verify cited answers or explicit insufficient-information responses without cross-tenant leakage.

### Tests for User Story 1 ⚠️

- [X] T019 [P] [US1] Add failing backend contract tests for `POST /api/v1/search/queries`, `GET /api/v1/chat/conversations`, `POST /api/v1/chat/conversations`, and `POST /api/v1/chat/conversations/{conversationId}/messages` in `backend/tests/Feature/Api/ChatSearchContractTest.php`
- [X] T020 [P] [US1] Add failing backend authorization and tenant-isolation tests for chat/search access in `backend/tests/Feature/Auth/ChatTenantIsolationTest.php`
- [X] T021 [P] [US1] Add failing backend workflow tests for cited-answer and insufficient-information outcomes in `backend/tests/Feature/Workflow/ChatResponseWorkflowTest.php`
- [X] T022 [P] [US1] Add failing Angular component tests for the chat shell, conversation rail, and citation panel in `frontend/src/app/features/chat/chat-page.component.spec.ts`
- [X] T023 [P] [US1] Add failing Playwright MVP journey test for sign-in plus question submission with citations in `frontend/tests/e2e/chat-mvp.spec.ts`

### Implementation for User Story 1

- [X] T024 [P] [US1] Create document-segment, chat-conversation, chat-message, message-citation, and search-query models in `backend/app/Models/DocumentSegment.php`, `backend/app/Models/ChatConversation.php`, `backend/app/Models/ChatMessage.php`, `backend/app/Models/MessageCitation.php`, and `backend/app/Models/SearchQuery.php`
- [X] T025 [P] [US1] Create repositories for tenant-scoped retrieval and conversation persistence in `backend/app/Repositories/DocumentSegmentRepository.php`, `backend/app/Repositories/ChatConversationRepository.php`, and `backend/app/Repositories/SearchQueryRepository.php`
- [X] T026 [US1] Implement semantic retrieval and citation assembly services in `backend/app/Services/Search/SemanticSearchService.php` and `backend/app/Services/Chat/CitationService.php`
- [X] T027 [US1] Implement tenant-scoped chat orchestration and insufficient-information handling in `backend/app/Services/Chat/ChatService.php`
- [X] T028 [US1] Integrate Qdrant and Ollama adapters for embeddings and answer generation in `backend/app/Services/AI/EmbeddingService.php`, `backend/app/Services/AI/ChatCompletionService.php`, and `backend/config/services.php`
- [X] T029 [US1] Implement chat and search request validators and API controllers in `backend/app/Http/Requests/SearchQueryRequest.php`, `backend/app/Http/Requests/ChatMessageRequest.php`, `backend/app/Http/Controllers/Api/V1/SearchController.php`, and `backend/app/Http/Controllers/Api/V1/ChatController.php`
- [X] T030 [US1] Wire chat/search routes, audit logging, and service transactions for question handling in `backend/routes/api.php` and `backend/app/Services/Chat/ChatService.php`
- [X] T031 [P] [US1] Create Angular chat and search data services in `frontend/src/app/features/chat/chat-api.service.ts` and `frontend/src/app/features/search/search-api.service.ts`
- [X] T032 [P] [US1] Build ChatGPT-style chat layout, composer, and conversation rail in `frontend/src/app/features/chat/chat-page.component.ts`, `frontend/src/app/features/chat/chat-page.component.html`, and `frontend/src/app/features/chat/chat-page.component.scss`
- [X] T033 [P] [US1] Build citation list and semantic search result components in `frontend/src/app/features/chat/citation-panel.component.ts` and `frontend/src/app/features/search/search-results.component.ts`
- [X] T034 [US1] Connect Italian UX states for loading, empty, error, and insufficient-information messaging in `frontend/src/assets/i18n/it-IT.json` and `frontend/src/app/features/chat/`

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Upload and Prepare Documents (Priority: P2)

**Goal**: Let privileged tenant users upload documents, track processing status, and make successfully indexed content available to search and chat.

**Independent Test**: Sign in as an operator, upload a supported document, observe status transitions through queued/processing/indexed, and confirm the indexed document becomes retrievable in search/chat.

### Tests for User Story 2 ⚠️

- [X] T035 [P] [US2] Add failing backend contract tests for `GET /api/v1/documents`, `POST /api/v1/documents`, `GET /api/v1/documents/{documentId}`, and `POST /api/v1/documents/{documentId}/retry` in `backend/tests/Feature/Api/DocumentContractTest.php`
- [X] T036 [P] [US2] Add failing backend authorization and workflow transition tests for upload and retry actions in `backend/tests/Feature/Auth/DocumentAuthorizationTest.php` and `backend/tests/Feature/Workflow/DocumentLifecycleTest.php`
- [X] T037 [P] [US2] Add failing Angular component tests for document upload and document list status views in `frontend/src/app/features/documents/document-upload.component.spec.ts` and `frontend/src/app/features/documents/document-list.component.spec.ts`
- [X] T038 [P] [US2] Add failing Playwright E2E test for operator upload and processing-status tracking in `frontend/tests/e2e/document-upload.spec.ts`

### Implementation for User Story 2

- [X] T039 [P] [US2] Create document migration updates and the full document model fields required for upload lifecycle tracking in `backend/database/migrations/` and `backend/app/Models/Document.php`
- [X] T040 [P] [US2] Create document repository and upload request validators in `backend/app/Repositories/DocumentRepository.php`, `backend/app/Http/Requests/DocumentUploadRequest.php`, and `backend/app/Http/Requests/DocumentRetryRequest.php`
- [X] T041 [US2] Implement document storage, lifecycle transition, and retry orchestration in `backend/app/Services/Documents/DocumentService.php`
- [X] T042 [US2] Implement asynchronous ingestion jobs for extraction, segmentation, embedding, indexing, and failure handling in `backend/app/Jobs/ProcessDocumentJob.php`, `backend/app/Jobs/IndexDocumentSegmentsJob.php`, and `backend/app/Services/Documents/DocumentProcessingService.php`
- [X] T043 [US2] Implement document API controllers and tenant-scoped routes in `backend/app/Http/Controllers/Api/V1/DocumentController.php` and `backend/routes/api.php`
- [X] T044 [US2] Extend audit logging for upload, processing success/failure, and retry events in `backend/app/Services/Audit/AuditService.php` and `backend/app/Services/Documents/DocumentService.php`
- [X] T045 [P] [US2] Create Angular document API service and status models in `frontend/src/app/features/documents/document-api.service.ts` and `frontend/src/app/features/documents/document.models.ts`
- [X] T046 [P] [US2] Build upload form, document list, and document status badge components in `frontend/src/app/features/documents/document-upload.component.ts`, `frontend/src/app/features/documents/document-list.component.ts`, and `frontend/src/app/features/documents/document-status-badge.component.ts`
- [X] T047 [US2] Connect Italian validation messages, duplicate-upload handling, and loading/error states for document workflows in `frontend/src/assets/i18n/it-IT.json` and `frontend/src/app/features/documents/`
- [ ] T048 [US2] Integrate indexed-document availability from ingestion into semantic retrieval services in `backend/app/Services/Documents/DocumentProcessingService.php` and `backend/app/Services/Search/SemanticSearchService.php`

**Checkpoint**: At this point, User Stories 1 and 2 should both work independently

---

## Phase 5: User Story 3 - Review Tenant Activity and Access History (Priority: P3)

**Goal**: Let authorized tenant administrators review audit history for security-sensitive and business-critical events with filtering and tenant isolation.

**Independent Test**: Sign in as a privileged tenant administrator, filter audit events by actor, date, event type, and outcome, and verify only same-tenant events appear while unauthorized access is denied and logged.

### Tests for User Story 3 ⚠️

- [X] T049 [P] [US3] Add failing backend contract tests for `GET /api/v1/audit-events` in `backend/tests/Feature/Api/AuditContractTest.php`
- [X] T050 [P] [US3] Add failing backend authorization and tenant-boundary tests for audit-log access in `backend/tests/Feature/Auth/AuditAuthorizationTest.php`
- [X] T051 [P] [US3] Add failing Angular component tests for audit table filtering and empty states in `frontend/src/app/features/audit/audit-page.component.spec.ts`
- [X] T052 [P] [US3] Add failing Playwright E2E test for audit-log filtering and denied access handling in `frontend/tests/e2e/audit-log.spec.ts`

### Implementation for User Story 3

- [X] T053 [P] [US3] Create audit-event filter DTOs and repository query methods in `backend/app/DataTransferObjects/AuditEventFilters.php` and `backend/app/Repositories/AuditEventRepository.php`
- [X] T054 [US3] Implement audit-log query service with tenant scoping and role checks in `backend/app/Services/Audit/AuditQueryService.php`
- [X] T055 [US3] Implement audit-log controller, request validator, and route registration in `backend/app/Http/Requests/AuditEventIndexRequest.php`, `backend/app/Http/Controllers/Api/V1/AuditController.php`, and `backend/routes/api.php`
- [X] T056 [US3] Ensure denied audit access attempts are recorded through the shared audit service in `backend/app/Services/Auth/AuthorizationService.php` and `backend/app/Services/Audit/AuditService.php`
- [X] T057 [P] [US3] Create Angular audit API service and filter models in `frontend/src/app/features/audit/audit-api.service.ts` and `frontend/src/app/features/audit/audit.models.ts`
- [X] T058 [P] [US3] Build audit-log page, filter bar, and paginated event table in `frontend/src/app/features/audit/audit-page.component.ts`, `frontend/src/app/features/audit/audit-filter-form.component.ts`, and `frontend/src/app/features/audit/audit-table.component.ts`
- [X] T059 [US3] Add Italian filter labels, denied-state messaging, and audit navigation integration in `frontend/src/assets/i18n/it-IT.json` and `frontend/src/app/app.routes.ts`

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T060 [P] Update developer setup and verification notes to match implemented workflows in `specs/001-private-doc-assistant/quickstart.md` and `README.md`
- [ ] T061 Harden cross-tenant regression coverage across backend and Playwright suites in `backend/tests/Feature/` and `frontend/tests/e2e/`
- [ ] T062 [P] Add backend service-level unit tests for auth, document, chat, and audit orchestration in `backend/tests/Unit/Services/`
- [ ] T063 [P] Verify Italian localization coverage and UX consistency across auth, chat, document, search, and audit screens in `frontend/src/assets/i18n/it-IT.json`
- [ ] T064 Run full quickstart validation and capture any implementation gaps in `specs/001-private-doc-assistant/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - blocks all user stories
- **User Stories (Phases 3-5)**: Depend on Foundational completion
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Phase 2 and is the MVP slice
- **User Story 2 (P2)**: Starts after Phase 2 and integrates with the retrieval foundations used by US1
- **User Story 3 (P3)**: Starts after Phase 2 and reuses shared auth/audit foundations without requiring US1 or US2 completion for basic audit listing

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Backend models and repositories before service orchestration
- Services before controllers/routes
- Backend contracts before frontend integration
- Frontend state/services before UI composition

### Parallel Opportunities

- Setup tasks `T003-T006` can run in parallel after `T001-T002`
- Foundational tasks `T007-T018` have parallel workstreams across backend, frontend, and infrastructure
- In US1, tests `T019-T023`, backend model/repository tasks `T024-T025`, and frontend service/UI tasks `T031-T033` can run in parallel inside their dependency bands
- In US2, tests `T035-T038`, backend upload model/repository tasks `T039-T040`, and frontend tasks `T045-T046` can run in parallel
- In US3, tests `T049-T052`, backend audit filtering task `T053`, and frontend tasks `T057-T058` can run in parallel

---

## Parallel Example: User Story 1

```bash
# Launch US1 failing tests together
Task: "T019 [US1] backend chat/search contract tests in backend/tests/Feature/Api/ChatSearchContractTest.php"
Task: "T020 [US1] backend tenant-isolation tests in backend/tests/Feature/Auth/ChatTenantIsolationTest.php"
Task: "T022 [US1] Angular chat component tests in frontend/src/app/features/chat/chat-page.component.spec.ts"
Task: "T023 [US1] Playwright MVP flow in frontend/tests/e2e/chat-mvp.spec.ts"

# Launch US1 backend and frontend build-out in parallel after tests exist
Task: "T024 [US1] create conversation and citation models in backend/app/Models/"
Task: "T025 [US1] create retrieval repositories in backend/app/Repositories/"
Task: "T031 [US1] create chat and search API services in frontend/src/app/features/"
Task: "T032 [US1] build ChatGPT-style chat page in frontend/src/app/features/chat/"
```

---

## Parallel Example: User Story 2

```bash
# Launch US2 failing tests together
Task: "T035 [US2] document contract tests in backend/tests/Feature/Api/DocumentContractTest.php"
Task: "T036 [US2] document auth/workflow tests in backend/tests/Feature/Auth/DocumentAuthorizationTest.php and backend/tests/Feature/Workflow/DocumentLifecycleTest.php"
Task: "T037 [US2] Angular document component tests in frontend/src/app/features/documents/"
Task: "T038 [US2] Playwright document upload flow in frontend/tests/e2e/document-upload.spec.ts"

# Launch US2 model/service/UI workstreams in parallel after tests exist
Task: "T039 [US2] document lifecycle model and migrations in backend/database/migrations/ and backend/app/Models/Document.php"
Task: "T040 [US2] repository and validators in backend/app/Repositories/DocumentRepository.php and backend/app/Http/Requests/"
Task: "T045 [US2] document API service in frontend/src/app/features/documents/document-api.service.ts"
Task: "T046 [US2] upload and list UI in frontend/src/app/features/documents/"
```

---

## Parallel Example: User Story 3

```bash
# Launch US3 failing tests together
Task: "T049 [US3] audit contract tests in backend/tests/Feature/Api/AuditContractTest.php"
Task: "T050 [US3] audit authorization tests in backend/tests/Feature/Auth/AuditAuthorizationTest.php"
Task: "T051 [US3] Angular audit page tests in frontend/src/app/features/audit/audit-page.component.spec.ts"
Task: "T052 [US3] Playwright audit-log flow in frontend/tests/e2e/audit-log.spec.ts"

# Launch US3 backend and frontend work in parallel after tests exist
Task: "T053 [US3] audit filter query objects in backend/app/DataTransferObjects/ and backend/app/Repositories/AuditEventRepository.php"
Task: "T057 [US3] audit API service and models in frontend/src/app/features/audit/"
Task: "T058 [US3] audit page and table components in frontend/src/app/features/audit/"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. Validate sign-in, tenant isolation, and cited chat end to end
5. Demo the ChatGPT-style tenant chat experience as the MVP

### Incremental Delivery

1. Setup + Foundational establish auth, tenant boundaries, and shared contracts
2. Add User Story 1 for searchable cited chat
3. Add User Story 2 for document ingestion and indexed knowledge freshness
4. Add User Story 3 for tenant-visible audit oversight
5. Finish with regression, localization, and quickstart validation

### Parallel Team Strategy

1. One stream owns backend/auth foundations
2. One stream owns frontend shell and localization foundations
3. After Phase 2:
   - Stream A: US1 chat/search backend + frontend
   - Stream B: US2 document ingestion backend + frontend
   - Stream C: US3 audit backend + frontend

---

## Notes

- [P] tasks = different files, no dependencies on incomplete tasks in the same band
- [Story] labels map tasks to specific user stories for traceability
- Suggested MVP scope: Phase 3 / User Story 1 only
