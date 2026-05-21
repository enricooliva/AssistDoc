# Tasks: Document List Management

**Input**: Design documents from `/specs/007-document-list/`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/document-list-management.md`, `quickstart.md`

**Tests**: Tests are mandatory. Write failing tests before implementation tasks for each phase and story.

**Organization**: Tasks are grouped by user story so each story can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel when the referenced files do not overlap and phase dependencies are satisfied
- **[Story]**: User story label for traceability (`[US1]`, `[US2]`)
- Every task includes an exact file path

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Prepare the approved design artifacts and test entry points for document list pagination and soft delete work

- [X] T001 Validate the implementation contract against the current feature scope in `specs/007-document-list/contracts/document-list-management.md` and `specs/007-document-list/spec.md`
- [X] T002 [P] Add backend feature test scaffolding for document list and document delete flows in `backend/tests/Feature/Documents/DocumentListTest.php` and `backend/tests/Feature/Documents/DocumentDeleteTest.php`
- [X] T003 [P] Add frontend component and E2E scaffolding for paginated list and delete confirmation flows in `frontend/src/app/features/documents/document-list.component.spec.ts` and `frontend/tests/e2e/document-list-management.spec.ts`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Introduce shared persistence and model changes required by both browsing and soft delete flows

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T004 Create document soft-delete persistence fields in `backend/database/migrations/2026_05_21_000001_add_soft_delete_to_documents_table.php`
- [X] T005 [P] Extend shared document and user models for soft-delete metadata and relations in `backend/app/Models/Document.php` and `backend/app/Models/User.php`
- [X] T006 [P] Add shared repository helpers for active-document pagination and deleted-document lookup in `backend/app/Repositories/DocumentRepository.php`
- [X] T007 [P] Add validated paging request handling for document list queries in `backend/app/Http/Requests/DocumentIndexRequest.php`

**Checkpoint**: Foundation ready. User stories can now proceed in priority order or in parallel if staffed.

---

## Phase 3: User Story 1 - Browse Documents (Priority: P1) 🎯 MVP

**Goal**: Let authenticated tenant users browse a paginated list of documents with tag and uploader information

**Independent Test**: Sign in as an authorized tenant user, open the documents page, and verify the list shows tenant-scoped paginated results with tags, uploader names, and empty/loading/error states in Italian

### Tests for User Story 1

- [X] T008 [P] [US1] Add backend feature coverage for paginated tenant-scoped document listing in `backend/tests/Feature/Documents/DocumentListTest.php`
- [X] T009 [P] [US1] Add authorization and tenant-isolation coverage for document browsing in `backend/tests/Feature/Auth/AuthorizationTest.php` and `backend/tests/Feature/Auth/TenantIsolationTest.php`
- [X] T010 [P] [US1] Add frontend component coverage for pagination, loading, empty, error, tags, and uploader display in `frontend/src/app/features/documents/document-list.component.spec.ts`
- [X] T011 [P] [US1] Add browser flow coverage for opening the paginated document list in `frontend/tests/e2e/document-list-management.spec.ts`

### Implementation for User Story 1

- [X] T012 [US1] Implement paginated tenant-scoped document queries and response mapping in `backend/app/Repositories/DocumentRepository.php`
- [X] T013 [US1] Implement list orchestration and page metadata in `backend/app/Services/Documents/DocumentService.php`
- [X] T014 [US1] Wire `DocumentIndexRequest` into `backend/app/Http/Controllers/Api/V1/DocumentController.php` and expose the paginated list route in `backend/routes/api.php`
- [X] T015 [US1] Extend document list API types for pagination metadata in `frontend/src/app/features/documents/document.models.ts`
- [X] T016 [US1] Implement paginated document loading, page state, and error handling in `frontend/src/app/features/documents/document-api.service.ts`
- [X] T017 [US1] Rework the document list UI to render tags, uploader metadata, pagination controls, and Italian empty/error/loading states in `frontend/src/app/features/documents/document-list.component.ts`

**Checkpoint**: User Story 1 should be independently functional and testable as the MVP.

---

## Phase 4: User Story 2 - Soft Delete a Document (Priority: P2)

**Goal**: Let authorized users soft-delete a document while preserving traceability and removing related chunks from active retrieval

**Independent Test**: Sign in as an authorized operator, delete a document from the list, verify it disappears from the normal list, confirm a second delete fails clearly, and ensure unauthorized users cannot delete documents

### Tests for User Story 2

- [X] T018 [P] [US2] Add backend feature coverage for successful, duplicate, unauthorized, and not-found soft delete cases in `backend/tests/Feature/Documents/DocumentDeleteTest.php`
- [X] T019 [P] [US2] Add backend unit coverage for delete orchestration, segment retirement, and queued Qdrant cleanup in `backend/tests/Unit/Documents/DocumentDeletionServiceTest.php`
- [X] T020 [P] [US2] Add frontend component coverage for delete confirmation, disabled states, and optimistic row removal in `frontend/src/app/features/documents/document-list.component.spec.ts`
- [X] T021 [P] [US2] Add browser flow coverage for confirming and completing a soft delete from the document list in `frontend/tests/e2e/document-list-management.spec.ts`

### Implementation for User Story 2

- [X] T022 [US2] Implement soft-delete workflow and duplicate-delete handling in `backend/app/Repositories/DocumentRepository.php` and `backend/app/Services/Documents/DocumentService.php`
- [X] T023 [US2] Implement segment retirement and queued vector cleanup in `backend/app/Repositories/DocumentSegmentRepository.php`, `backend/app/Services/Documents/DocumentIndexerService.php`, and `backend/app/Jobs/DeleteDocumentVectorsJob.php`
- [X] T024 [US2] Expose the DELETE endpoint, delete response mapping, and audit recording in `backend/app/Http/Controllers/Api/V1/DocumentController.php` and `backend/routes/api.php`
- [X] T025 [US2] Extend document API models and delete client methods in `frontend/src/app/features/documents/document.models.ts` and `frontend/src/app/features/documents/document-api.service.ts`
- [X] T026 [US2] Implement confirmation UI, delete action wiring, loading state, and removed-row behavior in `frontend/src/app/features/documents/document-list.component.ts`

**Checkpoint**: User Story 2 should be independently functional and testable on top of the US1 list flow.

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: Final verification and consistency across backend, frontend, and quickstart guidance

- [X] T027 [P] Update API route and authorization coverage for the new DELETE contract in `backend/tests/Feature/ApiRoutesTest.php`
- [X] T028 [P] Verify Italian copy, empty/loading/error messaging, and role-based action visibility in `frontend/src/app/features/documents/document-list.component.ts`, `frontend/src/app/features/documents/documents-page.component.ts`, and `frontend/src/assets/i18n/it-IT.json`
- [X] T029 Run and document the feature verification flow in `specs/007-document-list/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies; can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion; blocks all user stories
- **User Story 1 (Phase 3)**: Depends on Foundational completion
- **User Story 2 (Phase 4)**: Depends on Foundational completion and builds on the US1 list surface
- **Polish (Phase 5)**: Depends on the user stories selected for delivery

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Phase 2 and is the MVP slice
- **User Story 2 (P2)**: Starts after Phase 2 but should be implemented after US1 because its UI action lives inside the paginated list

### Within Each User Story

- Tests must be written and fail before implementation
- Backend persistence and service tasks come before controller and UI wiring
- API types and client integration come before final component behavior

### Parallel Opportunities

- T002 and T003 can run in parallel during setup
- T005, T006, and T007 can run in parallel after the migration shape is defined
- In US1, T008 through T011 can run in parallel, then T015 and T016 can run in parallel after T012 through T014 land
- In US2, T018 through T021 can run in parallel, then T023 and T025 can run in parallel after T022 stabilizes the delete workflow
- T027 and T028 can run in parallel during polish

---

## Parallel Example: User Story 1

```bash
# Launch the US1 failing tests together
Task: "T008 backend/tests/Feature/Documents/DocumentListTest.php"
Task: "T009 backend/tests/Feature/Auth/AuthorizationTest.php and backend/tests/Feature/Auth/TenantIsolationTest.php"
Task: "T010 frontend/src/app/features/documents/document-list.component.spec.ts"
Task: "T011 frontend/tests/e2e/document-list-management.spec.ts"

# Launch the US1 API/client wiring after backend list semantics are stable
Task: "T015 frontend/src/app/features/documents/document.models.ts"
Task: "T016 frontend/src/app/features/documents/document-api.service.ts"
```

---

## Parallel Example: User Story 2

```bash
# Launch the US2 failing tests together
Task: "T018 backend/tests/Feature/Documents/DocumentDeleteTest.php"
Task: "T019 backend/tests/Unit/Documents/DocumentDeletionServiceTest.php"
Task: "T020 frontend/src/app/features/documents/document-list.component.spec.ts"
Task: "T021 frontend/tests/e2e/document-list-management.spec.ts"

# Launch cleanup and client wiring after delete semantics are stable
Task: "T023 backend/app/Repositories/DocumentSegmentRepository.php and backend/app/Services/Documents/DocumentIndexerService.php"
Task: "T025 frontend/src/app/features/documents/document.models.ts and frontend/src/app/features/documents/document-api.service.ts"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. Stop and validate that tenant users can browse paginated documents with tags and uploader information

### Incremental Delivery

1. Deliver Setup + Foundational to establish shared persistence and query rules
2. Deliver US1 as the browseable paginated document inventory
3. Deliver US2 to add safe soft deletion with Qdrant coherence
4. Finish with polish and quickstart verification

### Parallel Team Strategy

1. One engineer handles backend persistence and service changes
2. One engineer handles frontend list and delete interaction changes
3. One engineer handles feature, component, and E2E tests once contracts are stable

---

## Notes

- `[P]` tasks touch different files and can run in parallel once dependencies are satisfied
- Every user story remains independently testable
- Keep Qdrant cleanup scoped to `tenant_id` plus `document_id` so stored chunks remain consistent with the soft-deleted document state
