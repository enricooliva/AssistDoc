# Tasks: Text Content Ingestion

**Input**: Design documents from `/specs/013-text-content-ingestion/`
**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/text-content-ingestion-openapi.yaml](./contracts/text-content-ingestion-openapi.yaml)

**Tests**: Tests are mandatory. Add or update tests before implementation for each phase and user story.

**Organization**: Tasks are grouped by user story so each story can be implemented and validated independently.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Align specification artifacts and test entry points with the implementation branch

- [X] T001 Update the feature task inventory and implementation references in `/home/enricooliva/Workspace/AssistDoc/specs/013-text-content-ingestion/tasks.md`
- [X] T002 [P] Add the new direct-text API scenario to `/home/enricooliva/Workspace/AssistDoc/specs/013-text-content-ingestion/contracts/text-content-ingestion-openapi.yaml`
- [X] T003 [P] Extend document quickstart verification commands in `/home/enricooliva/Workspace/AssistDoc/specs/013-text-content-ingestion/quickstart.md`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Establish shared schema, contracts, serialization, and base tests required by every ingestion path

**⚠️ CRITICAL**: No user story work should start until this phase is complete

- [X] T004 [P] Add failing backend coverage for `source_type` persistence and normalized document payloads in `/home/enricooliva/Workspace/AssistDoc/backend/tests/Feature/Documents/DocumentListTest.php` and `/home/enricooliva/Workspace/AssistDoc/backend/tests/Feature/Documents/DocumentUploadTest.php`
- [X] T005 [P] Add failing frontend model and rendering coverage for source labels and failure reasons in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-list.component.spec.ts` and `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-upload.component.spec.ts`
- [X] T006 Create the documents schema migration for `source_type` and any supporting defaults in `/home/enricooliva/Workspace/AssistDoc/backend/database/migrations/`
- [X] T007 [P] Extend the document domain model and repository mappings for `source_type`, failure messaging, and uniform payload fields in `/home/enricooliva/Workspace/AssistDoc/backend/app/Models/Document.php` and `/home/enricooliva/Workspace/AssistDoc/backend/app/Repositories/DocumentRepository.php`
- [X] T008 [P] Update document API serialization for list/detail/create responses in `/home/enricooliva/Workspace/AssistDoc/backend/app/Services/Documents/DocumentService.php` and `/home/enricooliva/Workspace/AssistDoc/backend/app/Http/Controllers/Api/V1/DocumentController.php`
- [X] T009 [P] Extend frontend document types to include `sourceType`, failure details, and shared create-response fields in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document.models.ts`

**Checkpoint**: Shared document metadata and contracts are ready for both file upload and direct text ingestion

---

## Phase 3: User Story 1 - Caricare un documento ricercabile (Priority: P1) 🎯 MVP

**Goal**: Preserve and harden the current file-upload path so uploaded documents continue to become searchable through the existing private-storage and indexing pipeline

**Independent Test**: Upload a valid PDF or TXT document and verify the API returns a `file` source, the document reaches `ready`, and searchable segments are created without using the direct-text flow

### Tests for User Story 1 ⚠️

- [X] T010 [P] [US1] Add contract and feature coverage for file upload responses including `sourceType=file` in `/home/enricooliva/Workspace/AssistDoc/backend/tests/Feature/Documents/DocumentUploadTest.php`
- [X] T011 [P] [US1] Add processing coverage for uploaded-file extraction and searchable segment creation in `/home/enricooliva/Workspace/AssistDoc/backend/tests/Unit/Documents/DocumentProcessingServiceTest.php`
- [X] T012 [P] [US1] Add frontend component coverage for the file-upload mode, validations, and Italian messaging in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-upload.component.spec.ts`

### Implementation for User Story 1

- [X] T013 [US1] Extend file-upload validation and metadata normalization for `sourceLabel` and tags in `/home/enricooliva/Workspace/AssistDoc/backend/app/Http/Requests/DocumentUploadRequest.php`
- [X] T014 [US1] Update file-based document creation to persist `source_type=file`, preserve private storage, and trigger default profile processing in `/home/enricooliva/Workspace/AssistDoc/backend/app/Services/Documents/DocumentService.php`
- [X] T015 [US1] Keep uploaded-file extraction and indexing aligned with the shared retrieval/chunking pipeline in `/home/enricooliva/Workspace/AssistDoc/backend/app/Services/Documents/DocumentProcessingService.php` and `/home/enricooliva/Workspace/AssistDoc/backend/app/Services/Documents/DocumentIndexerService.php`
- [X] T016 [US1] Update the document upload UI to present the file-ingestion mode explicitly with Bootstrap and `ngx-formly` controls in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-upload.component.ts`
- [X] T017 [US1] Update file-upload API wiring and response handling in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-api.service.ts`

**Checkpoint**: File upload remains fully functional and independently testable with the new unified document contract

---

## Phase 4: User Story 2 - Inserire testo copiato e incollato (Priority: P1)

**Goal**: Let authorized users paste text directly in the documents area and process it through the same secure storage and semantic-indexing pipeline used for uploaded files

**Independent Test**: Submit valid pasted text with a user label and verify the API stores it as a `text` source, the document reaches `ready`, and the content becomes searchable without uploading any file

### Tests for User Story 2 ⚠️

- [X] T018 [P] [US2] Add failing feature coverage for direct-text creation, validation, and tenant-scoped authorization in `/home/enricooliva/Workspace/AssistDoc/backend/tests/Feature/Documents/DocumentUploadTest.php`
- [X] T019 [P] [US2] Add failing unit coverage for storing normalized pasted text, enforcing the configured max input length, and reusing the processing pipeline in `/home/enricooliva/Workspace/AssistDoc/backend/tests/Unit/Documents/DocumentProcessingServiceTest.php`
- [X] T020 [P] [US2] Add failing retrieval coverage proving direct-text documents are returned by semantic search or chat citations in `/home/enricooliva/Workspace/AssistDoc/backend/tests/Feature/Chat/SubmitChatMessageTest.php`
- [X] T021 [P] [US2] Add failing frontend coverage for mode switching, pasted-text validation, and submit behavior in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-upload.component.spec.ts`

### Implementation for User Story 2

- [X] T022 [P] [US2] Create a dedicated request validator for direct text submission in `/home/enricooliva/Workspace/AssistDoc/backend/app/Http/Requests/DocumentTextCreateRequest.php`
- [X] T023 [P] [US2] Add the direct-text endpoint and role-protected route in `/home/enricooliva/Workspace/AssistDoc/backend/app/Http/Controllers/Api/V1/DocumentController.php` and `/home/enricooliva/Workspace/AssistDoc/backend/routes/api.php`
- [X] T024 [US2] Implement direct-text document creation with private plain-text storage, `source_type=text`, and audit-safe metadata in `/home/enricooliva/Workspace/AssistDoc/backend/app/Services/Documents/DocumentService.php`
- [X] T025 [US2] Bind direct-text length validation to the existing embedding/RAG configuration in `/home/enricooliva/Workspace/AssistDoc/backend/app/Http/Requests/DocumentTextCreateRequest.php` and `/home/enricooliva/Workspace/AssistDoc/backend/config/rag.php`
- [X] T026 [US2] Reuse extraction and preparation for plain-text artifacts without introducing a second indexing path in `/home/enricooliva/Workspace/AssistDoc/backend/app/Services/Documents/DocumentProcessingService.php`
- [X] T027 [US2] Extend the Angular documents API client with the direct-text create call in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-api.service.ts`
- [X] T028 [US2] Add the direct-text mode, Formly fields, Italian validations, and submit flow to the existing documents form in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-upload.component.ts`

**Checkpoint**: Direct text ingestion works independently from file upload and produces searchable content through the same pipeline

---

## Phase 5: User Story 3 - Monitorare esiti e problemi di acquisizione (Priority: P2)

**Goal**: Expose source-aware status, failure reasons, retry behavior, and cleanup so users can understand processing outcomes and remove content safely

**Independent Test**: Create valid and invalid content items, verify status and failure details in the documents UI, retry a failed item, and confirm delete removes related searchable artifacts

### Tests for User Story 3 ⚠️

- [X] T029 [P] [US3] Add failing feature coverage for failed processing, retry transitions, and delete cleanup of segments and vectors in `/home/enricooliva/Workspace/AssistDoc/backend/tests/Feature/Documents/DocumentRetryTest.php` and `/home/enricooliva/Workspace/AssistDoc/backend/tests/Feature/Documents/DocumentDeleteTest.php`
- [X] T030 [P] [US3] Add failing unit coverage for deletion cleanup of document segments, citations, and collection points in `/home/enricooliva/Workspace/AssistDoc/backend/tests/Unit/Documents/DocumentDeletionServiceTest.php` and `/home/enricooliva/Workspace/AssistDoc/backend/tests/Unit/Documents/DocumentIndexerServiceTest.php`
- [X] T031 [P] [US3] Add failing repository or integration coverage for removing persisted message citations tied to deleted document segments in `/home/enricooliva/Workspace/AssistDoc/backend/tests/Unit/Repositories/MessageCitationRepositoryTest.php` or `/home/enricooliva/Workspace/AssistDoc/backend/tests/Feature/Chat/ConversationShowTest.php`
- [X] T032 [P] [US3] Add failing frontend coverage for source badges, failure messages, retry visibility, and list refresh behavior in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-list.component.spec.ts` and `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-preparation-form.component.spec.ts`

### Implementation for User Story 3

- [X] T033 [US3] Surface consistent failure messages, retry states, and preparation-run metadata for both source types in `/home/enricooliva/Workspace/AssistDoc/backend/app/Services/Documents/DocumentService.php` and `/home/enricooliva/Workspace/AssistDoc/backend/app/Http/Controllers/Api/V1/DocumentController.php`
- [X] T034 [US3] Ensure document deletion removes related segments and vector points for the active embedding collection in `/home/enricooliva/Workspace/AssistDoc/backend/app/Services/Documents/DocumentService.php`, `/home/enricooliva/Workspace/AssistDoc/backend/app/Services/Documents/DocumentIndexerService.php`, and `/home/enricooliva/Workspace/AssistDoc/backend/app/Repositories/DocumentSegmentRepository.php`
- [X] T035 [US3] Remove persisted message citations or equivalent search references tied to deleted document segments in `/home/enricooliva/Workspace/AssistDoc/backend/app/Repositories/MessageCitationRepository.php` and `/home/enricooliva/Workspace/AssistDoc/backend/app/Services/Documents/DocumentService.php`
- [X] T036 [US3] Update the documents list and status UI to display source type, status, and failure reasons in Italian in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-list.component.ts` and `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-status-badge.component.ts`
- [X] T037 [US3] Update retry and preparation-run interactions to stay coherent for file and text documents in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-preparation-form.component.ts` and `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-api.service.ts`

**Checkpoint**: Users can understand document outcomes, retry failures, and trust that deletion removes searchable artifacts

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final consistency, regression coverage, and documentation across all stories

- [X] T038 [P] Add or update end-to-end coverage for file upload plus direct-text ingestion in `/home/enricooliva/Workspace/AssistDoc/frontend/tests/e2e/`
- [X] T039 [P] Verify localized copy, validation wording, and Bootstrap/Formly consistency across the documents feature in `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-upload.component.ts` and `/home/enricooliva/Workspace/AssistDoc/frontend/src/app/features/documents/document-list.component.ts`
- [ ] T040 Run the feature validation commands from `/home/enricooliva/Workspace/AssistDoc/specs/013-text-content-ingestion/quickstart.md` and record any follow-up fixes in `/home/enricooliva/Workspace/AssistDoc/specs/013-text-content-ingestion/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1: Setup**: No dependencies
- **Phase 2: Foundational**: Depends on Phase 1 and blocks all user stories
- **Phase 3: US1**: Depends on Phase 2
- **Phase 4: US2**: Depends on Phase 2 and can proceed in parallel with US1 after the shared foundation is complete
- **Phase 5: US3**: Depends on Phases 3 and 4 because it relies on both ingestion modes and shared cleanup behavior
- **Phase 6: Polish**: Depends on all user story phases selected for delivery

### User Story Dependencies

- **US1**: Independent after foundational work; recommended MVP slice
- **US2**: Independent after foundational work; shares the same document schema and serialization
- **US3**: Depends on the document payload, ingestion, and cleanup paths delivered by US1 and US2

### Within Each User Story

- Tests must be written and fail before implementation
- Validation and request objects before service wiring
- Service changes before controller and API integration
- Backend contract changes before frontend consumption
- Story-specific UI changes after the underlying API contract is stable

### Parallel Opportunities

- `T002` and `T003` can run in parallel
- `T004` and `T005` can run in parallel before foundational implementation
- `T007`, `T008`, and `T009` can run in parallel after `T006`
- `T010`, `T011`, and `T012` can run in parallel within US1
- `T018`, `T019`, `T020`, and `T021` can run in parallel within US2
- `T022` and `T023` can run in parallel within US2
- `T029`, `T030`, `T031`, and `T032` can run in parallel within US3
- `T038` and `T039` can run in parallel in the polish phase

---

## Parallel Example: User Story 2

```bash
# Launch direct-text tests together
Task: "T018 [US2] Add failing feature coverage for direct-text creation, validation, and tenant-scoped authorization in backend/tests/Feature/Documents/DocumentUploadTest.php"
Task: "T019 [US2] Add failing unit coverage for storing normalized pasted text and reusing the processing pipeline in backend/tests/Unit/Documents/DocumentProcessingServiceTest.php"
Task: "T020 [US2] Add failing retrieval coverage proving direct-text documents are returned by semantic search or chat citations in backend/tests/Feature/Chat/SubmitChatMessageTest.php"
Task: "T021 [US2] Add failing frontend coverage for mode switching, pasted-text validation, and submit behavior in frontend/src/app/features/documents/document-upload.component.spec.ts"

# Implement the API surface in parallel where files do not overlap
Task: "T022 [US2] Create a dedicated request validator for direct text submission in backend/app/Http/Requests/DocumentTextCreateRequest.php"
Task: "T027 [US2] Extend the Angular documents API client with the direct-text create call in frontend/src/app/features/documents/document-api.service.ts"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. Validate file upload, extraction, and indexing independently
5. Demo or release the stabilized upload flow if needed

### Incremental Delivery

1. Finish Setup + Foundational to stabilize the shared document contract
2. Deliver US1 and validate the existing upload path against regressions
3. Deliver US2 and validate direct-text ingestion without reopening file-upload regressions
4. Deliver US3 to complete status transparency, retry, and cleanup guarantees
5. Finish with polish, E2E coverage, and quickstart verification

### Parallel Team Strategy

1. One developer handles schema and backend serialization in Phase 2 while another prepares frontend model and list tests
2. After Phase 2, one developer can stabilize US1 while another builds US2
3. Once both ingestion paths are merged, a third pass can focus on US3 cleanup, retry, and status UX

---

## Notes

- All tasks follow the required checklist format with task ID and file path
- `[P]` marks tasks that can run in parallel without waiting for another file in the same phase
- `[US1]`, `[US2]`, and `[US3]` map directly to the user stories in `/home/enricooliva/Workspace/AssistDoc/specs/013-text-content-ingestion/spec.md`
