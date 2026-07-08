# Tasks: Configured Qwen RAG Support

**Input**: Design documents from `/specs/006-qwen3-rag-support/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/rag-profile-management-openapi.yaml, quickstart.md

**Tests**: Tests are mandatory. Write failing tests before implementation tasks for each phase and story.

**Organization**: Tasks are grouped by user story so each story can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel when the referenced files do not overlap and phase dependencies are satisfied
- **[Story]**: User story label for traceability (`[US1]`, `[US2]`, `[US3]`)
- Every task includes an exact file path

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Prepare contracts and test scaffolding for configured Qwen retrieval plus multi-profile chunking work

- [X] T001 Copy the approved contract into implementation space by validating `specs/006-qwen3-rag-support/contracts/rag-profile-management-openapi.yaml`
- [X] T002 [P] Add backend feature test scaffolding for configured retrieval loading, chunking profiles, and preparation endpoints in `backend/tests/Feature/Rag/ConfiguredRetrievalProfileTest.php`, `backend/tests/Feature/Rag/ChunkingProfileManagementTest.php`, and `backend/tests/Feature/Documents/DocumentPreparationRunTest.php`
- [X] T003 [P] Add backend unit test scaffolding for tokenizer-aware services in `backend/tests/Unit/AI/TokenizerServiceTest.php`, `backend/tests/Unit/Documents/DocumentIndexerServiceTest.php`, and `backend/tests/Unit/Documents/DocumentProcessingServiceTest.php`
- [X] T004 [P] Add frontend and E2E scaffolding for automatic embedding upload flows without profile selectors in `frontend/src/app/features/documents/document-preparation-form.component.spec.ts` and `frontend/tests/e2e/rag-profile-management.spec.ts`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core persistence, services, and API wiring required before any user story work

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T005 Create relational schema for chunking profiles, preparation runs, validation failures, and document/profile foreign keys in `backend/database/migrations/2026_05_13_000001_add_rag_profile_tables.php`
- [X] T006 [P] Add Eloquent models for shared chunking and preparation entities in `backend/app/Models/ChunkingProfile.php`, `backend/app/Models/ChunkPreparationRun.php`, and `backend/app/Models/PreparationValidationFailure.php`
- [X] T007 [P] Extend document and segment models with profile-aware relationships in `backend/app/Models/Document.php` and `backend/app/Models/DocumentSegment.php`
- [X] T008 [P] Create repositories for shared chunking profile persistence and run lookup in `backend/app/Repositories/ChunkingProfileRepository.php` and `backend/app/Repositories/ChunkPreparationRunRepository.php`
- [X] T009 [P] Add shared request validation objects for automatic preparation APIs in `backend/app/Http/Requests/DocumentPreparationRunRequest.php` and `backend/app/Http/Requests/DocumentUploadRequest.php`
- [X] T010 [P] Implement tokenizer/profile infrastructure in `backend/app/Services/AI/TokenizerService.php` and refactor `backend/app/Services/AI/EmbeddingService.php` to resolve the configured Qwen profile, dimensions, and token limits
- [X] T011 [P] Add shared DTOs for configured retrieval, chunking, and preparation responses in `backend/app/DataTransferObjects/Rag/RetrievalModelProfileData.php`, `backend/app/DataTransferObjects/Rag/ChunkingProfileData.php`, and `backend/app/DataTransferObjects/Rag/ChunkPreparationRunData.php`
- [X] T012 Wire shared API routes for automatic document preparation, chunking profiles, and preparation runs in `backend/routes/api.php`

**Checkpoint**: Foundation ready. User stories can now proceed in priority order or in parallel if staffed.

---

## Phase 3: User Story 1 - Use the Configured Qwen Retrieval Profile (Priority: P1) 🎯 MVP

**Goal**: Load the retrieval model profile from configuration, use `Qwen` as the default, and keep runtime retrieval-profile selection out of the document workflow

**Independent Test**: Configure the default `Qwen` retrieval profile, start document preparation, and confirm the run and stored retrieval data keep the configured profile identity without any runtime retrieval-profile selectors or CRUD

### Tests for User Story 1 ⚠️

- [X] T013 [P] [US1] Add feature coverage for loading the configured retrieval profile and rejecting runtime CRUD in `backend/tests/Feature/Rag/ConfiguredRetrievalProfileTest.php`
- [X] T014 [P] [US1] Add feature coverage for starting and reading profile-aware preparation runs in `backend/tests/Feature/Documents/DocumentPreparationRunTest.php`
- [X] T015 [P] [US1] Add unit coverage for config-backed embedding resolution in `backend/tests/Unit/AI/EmbeddingServiceTest.php`
- [X] T016 [P] [US1] Add unit coverage for retrieval compatibility filtering in `backend/tests/Unit/Documents/DocumentProcessingServiceTest.php` and `backend/tests/Unit/Chat/ChatServiceTest.php`
- [X] T017 [P] [US1] Add operator UI and E2E coverage for default `Qwen` retrieval behavior and upload auto-embedding in `frontend/src/app/features/documents/document-upload.component.spec.ts` and `frontend/tests/e2e/rag-profile-management.spec.ts`

### Implementation for User Story 1

- [X] T018 [P] [US1] Define the default `Qwen` retrieval profile in `backend/config/rag.php`
- [X] T019 [US1] Implement retrieval profile loading and default resolution from configuration in `backend/app/Services/Rag/RetrievalModelProfileService.php`
- [X] T020 [US1] Implement preparation-run orchestration with the configured retrieval profile in `backend/app/Services/Documents/DocumentProcessingService.php` and `backend/app/Repositories/ChunkPreparationRunRepository.php`
- [X] T021 [US1] Extend indexing persistence to stamp preparation runs and model profile metadata on document segments and Qdrant payloads in `backend/app/Services/Documents/DocumentIndexerService.php` and `backend/app/Repositories/DocumentSegmentRepository.php`
- [X] T022 [US1] Enforce profile-compatible retrieval and query embedding selection in `backend/app/Services/Search/SemanticSearchService.php` and `backend/app/Services/Chat/ChatService.php`
- [X] T023 [US1] Preserve `askLlamaWithContext` as the final grounded answer prompt while allowing profile-specific generation model resolution in `backend/app/Services/AI/AiSearchService.php`
- [X] T024 [US1] Remove runtime retrieval-profile endpoints and keep only preparation-run endpoints in `backend/app/Http/Controllers/Api/V1/DocumentController.php` and `backend/app/Http/Controllers/Api/V1/ChatController.php`
- [X] T025 [US1] Add frontend models and API methods for upload-only document payloads in `frontend/src/app/features/documents/document.models.ts` and `frontend/src/app/features/documents/document-api.service.ts`
- [X] T026 [US1] Build the automatic upload embedding flow and integrate it into `frontend/src/app/features/documents/document-upload.component.ts` and `frontend/src/app/features/documents/documents-page.component.ts`

**Checkpoint**: User Story 1 is independently functional when the configured default `Qwen` profile is used automatically and retrieval data remains compatible without runtime profile management.

---

## Phase 4: User Story 2 - Use Configured Chunking Profiles (Priority: P2)

**Goal**: Automatically embed uploaded documents with configuration-backed token-based chunking profiles, including `small`, `medium`, and `large`, while keeping every generated chunk below the configured model token window

**Independent Test**: Seed or configure the default chunking profiles, upload a document, and confirm the system automatically starts runs for `small`, `medium`, and `large` without exposing chunking-profile selectors in the upload flow and without producing oversized chunks

### Tests for User Story 2 ⚠️

- [X] T027 [P] [US2] Add feature coverage for listing configured chunking profiles in `backend/tests/Feature/Rag/ChunkingProfileManagementTest.php`
- [X] T028 [P] [US2] Add unit coverage for configuration-backed chunking profile validation rules in `backend/tests/Unit/AI/TokenizerServiceTest.php`
- [X] T029 [P] [US2] Add unit coverage for profile-specific chunk generation metadata in `backend/tests/Unit/Documents/DocumentIndexerServiceTest.php`
- [X] T030 [P] [US2] Add frontend and E2E coverage for automatic chunking-profile embedding on upload without profile selectors in `frontend/src/app/features/documents/document-upload.component.spec.ts` and `frontend/tests/e2e/rag-profile-management.spec.ts`

### Implementation for User Story 2

- [X] T031 [US2] Implement configuration-synced chunking profile loading, default ordering, and validation rules in `backend/app/Services/Rag/ChunkingProfileService.php`, `backend/app/Repositories/ChunkingProfileRepository.php`, and the chunking profile seeders
- [X] T032 [US2] Implement token-based chunk size and overlap generation in `backend/app/Services/AI/TokenizerService.php` and `backend/app/Services/Documents/DocumentIndexerService.php`
- [X] T033 [US2] Require every upload-triggered preparation run to bind exactly one configured chunking profile in `backend/app/Services/Documents/DocumentProcessingService.php` and `backend/app/Http/Controllers/Api/V1/DocumentController.php`
- [X] T034 [US2] Add backend serialization for chunking profile responses in `backend/app/DataTransferObjects/Rag/ChunkingProfileData.php` and `backend/app/DataTransferObjects/Rag/ChunkPreparationRunData.php`
- [X] T035 [US2] Add frontend models and API methods for automatic chunking-profile embedding in `frontend/src/app/features/documents/document.models.ts` and `frontend/src/app/features/documents/document-api.service.ts`
- [X] T036 [US2] Build the upload page to start automatic chunking-profile embedding and keep selectors out of the upload flow in `frontend/src/app/features/documents/document-upload.component.ts` and `frontend/src/app/features/documents/documents-page.component.ts`

**Checkpoint**: User Story 2 is independently functional when uploaded documents automatically launch the configured `small`, `medium`, and `large` chunking runs without exposing profile selectors in the document-load path and without exceeding the token window.

---

## Phase 5: User Story 3 - Block Oversized Chunks Before Indexing (Priority: P3)

**Goal**: Enforce tokenizer limits during chunking so non-compliant chunks are re-split or the preparation run fails before indexing

**Independent Test**: Prepare a document that would exceed the selected profile limit, confirm the system re-splits or fails before publishing vectors, and verify failure details plus historical run state are exposed

### Tests for User Story 3 ⚠️

- [X] T037 [P] [US3] Add feature coverage for tokenizer-limit failure handling in `backend/tests/Feature/Documents/DocumentPreparationRunTest.php`
- [X] T038 [P] [US3] Add unit coverage for oversized chunk detection and overlap-safe re-splitting in `backend/tests/Unit/AI/TokenizerServiceTest.php` and `backend/tests/Unit/Documents/DocumentIndexerServiceTest.php`
- [X] T039 [P] [US3] Add unit coverage for failure recording and old-vector withdrawal in `backend/tests/Unit/Documents/DocumentProcessingServiceTest.php`
- [X] T040 [P] [US3] Add chat compatibility and fallback prompt coverage under incompatible or missing retrieval data in `backend/tests/Feature/Chat/SubmitChatMessageTest.php` and `frontend/tests/e2e/chat-mvp.spec.ts`

### Implementation for User Story 3

- [X] T041 [US3] Implement tokenizer-limit validation and compliant re-splitting rules in `backend/app/Services/AI/TokenizerService.php` and `backend/app/Services/Documents/DocumentIndexerService.php`
- [X] T042 [US3] Persist validation failures, failure states, and measured token counts in `backend/app/Models/PreparationValidationFailure.php`, `backend/app/Repositories/ChunkPreparationRunRepository.php`, and `backend/app/Services/Documents/DocumentProcessingService.php`
- [X] T043 [US3] Retire outdated segment rows and Qdrant vectors before activating replacement data in `backend/app/Repositories/DocumentSegmentRepository.php`, `backend/app/Services/Documents/DocumentIndexerService.php`, and `backend/app/Services/Documents/DocumentProcessingService.php`
- [X] T044 [US3] Surface tokenizer-limit failures and historical preparation-run details through `backend/app/Http/Controllers/Api/V1/DocumentController.php` and `backend/app/DataTransferObjects/Rag/ChunkPreparationRunData.php`
- [X] T045 [US3] Reflect preparation failure details and run history in the operator UI in `frontend/src/app/features/documents/document-status-badge.component.ts`, `frontend/src/app/features/documents/document-list.component.ts`, and `frontend/src/app/features/documents/documents-page.component.ts`

**Checkpoint**: All user stories are independently functional when invalid chunks never become searchable and operators can inspect why a run failed or what profile combination is active.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final hardening across ingestion, retrieval, UI, and documentation

- [X] T046 [P] Update implementation notes and operator verification steps in `specs/006-qwen3-rag-support/quickstart.md`
- [X] T047 Harden audit coverage for profile lifecycle and preparation outcomes in `backend/app/Services/Audit/AuditService.php`, `backend/app/Services/Documents/DocumentProcessingService.php`, and `backend/app/Services/Rag/RetrievalModelProfileService.php`
- [X] T048 [P] Add cross-cutting regression coverage for grounded Italian responses in `backend/tests/Unit/AI/ChatCompletionServiceTest.php`, `backend/tests/Unit/Chat/CitationServiceTest.php`, and `backend/tests/Feature/Chat/SubmitChatMessageTest.php`
- [X] T049 [P] Verify frontend Italian localization and loading/error states in `frontend/src/app/features/documents/document-preparation-form.component.ts` and `frontend/src/app/features/chat/chat-page.component.ts`
- [ ] T050 Run quickstart validation and capture any required contract or task follow-up in `specs/006-qwen3-rag-support/quickstart.md` and `specs/006-qwen3-rag-support/tasks.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1: Setup**: Starts immediately
- **Phase 2: Foundational**: Depends on Phase 1 and blocks all user stories
- **Phase 3: User Story 1**: Depends on Phase 2 and delivers the MVP
- **Phase 4: User Story 2**: Depends on Phase 2 and can start after the shared foundations are stable, but integrates cleanly after US1
- **Phase 5: User Story 3**: Depends on Phases 2, 3, and 4 because tokenizer-limit enforcement requires both profile and chunking-profile support
- **Phase 6: Polish**: Depends on all desired stories being complete

### User Story Dependencies

- **US1**: No dependency on other user stories after Foundational
- **US2**: No hard dependency on US1 after Foundational, but the preferred delivery order is US1 first because the configured retrieval profile is already exercised there
- **US3**: Depends on US1 and US2 because limit enforcement requires the configured retrieval profile and a selected chunking profile

### Within Each User Story

- Tests must be written and fail before implementation
- Persistence and models before orchestration services
- Services before controllers and frontend wiring
- Backend APIs before UI integration
- Story checkpoint must pass before moving to the next priority in a single-threaded delivery

### Parallel Opportunities

- T002, T003, and T004 can run in parallel during setup
- T006 through T011 can run in parallel once the migration shape in T005 is agreed
- In US1, T013 through T017 can run in parallel, and T025 plus T026 can proceed after T024 stabilizes the API
- In US2, T027 through T030 can run in parallel, and T035 plus T036 can proceed after T031 through T034 define the backend contract
- In US3, T037 through T040 can run in parallel, and T044 plus T045 can proceed after T041 through T043 stabilize failure semantics

---

## Parallel Example: User Story 1

```bash
# Launch the US1 failing tests together
Task: "T013 backend/tests/Feature/Rag/ConfiguredRetrievalProfileTest.php"
Task: "T014 backend/tests/Feature/Documents/DocumentPreparationRunTest.php"
Task: "T015 backend/tests/Unit/AI/EmbeddingServiceTest.php"
Task: "T016 backend/tests/Unit/Documents/DocumentProcessingServiceTest.php and backend/tests/Unit/Chat/ChatServiceTest.php"
Task: "T017 frontend/src/app/features/documents/document-preparation-form.component.spec.ts and frontend/tests/e2e/rag-profile-management.spec.ts"

# Launch the US1 backend model/profile implementation work after tests are failing
Task: "T018 backend/config/rag.php"
Task: "T019 backend/app/Services/Rag/RetrievalModelProfileService.php"
Task: "T021 backend/app/Services/Documents/DocumentIndexerService.php and backend/app/Repositories/DocumentSegmentRepository.php"
```

---

## Parallel Example: User Story 2

```bash
# Launch the US2 failing tests together
Task: "T027 backend/tests/Feature/Rag/ChunkingProfileManagementTest.php"
Task: "T028 backend/tests/Unit/AI/TokenizerServiceTest.php"
Task: "T029 backend/tests/Unit/Documents/DocumentIndexerServiceTest.php"
Task: "T030 frontend/src/app/features/documents/document-preparation-form.component.spec.ts and frontend/tests/e2e/rag-profile-management.spec.ts"

# Launch the UI wiring after backend contract work lands
Task: "T035 frontend/src/app/features/documents/document.models.ts and frontend/src/app/features/documents/document-api.service.ts"
Task: "T036 frontend/src/app/features/documents/document-preparation-form.component.ts and frontend/src/app/features/documents/documents-page.component.ts"
```

---

## Parallel Example: User Story 3

```bash
# Launch the US3 failing tests together
Task: "T037 backend/tests/Feature/Documents/DocumentPreparationRunTest.php"
Task: "T038 backend/tests/Unit/AI/TokenizerServiceTest.php and backend/tests/Unit/Documents/DocumentIndexerServiceTest.php"
Task: "T039 backend/tests/Unit/Documents/DocumentProcessingServiceTest.php"
Task: "T040 backend/tests/Feature/Chat/SubmitChatMessageTest.php and frontend/tests/e2e/chat-mvp.spec.ts"

# Launch the failure-surface work after tokenizer rules are stable
Task: "T042 backend/app/Models/PreparationValidationFailure.php and backend/app/Repositories/ChunkPreparationRunRepository.php"
Task: "T044 backend/app/Http/Controllers/Api/V1/DocumentController.php and backend/app/DataTransferObjects/Rag/ChunkPreparationRunData.php"
Task: "T045 frontend/src/app/features/documents/document-status-badge.component.ts and frontend/src/app/features/documents/document-list.component.ts"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. Stop and validate that a document can be processed with the configured `Qwen` profile and retrieve grounded answers through compatible data

### Incremental Delivery

1. Complete Setup + Foundational
2. Deliver US1 as the MVP for configuration-backed Qwen retrieval
3. Deliver US2 to make document upload automatically start the configured chunking-profile runs
4. Deliver US3 to harden tokenizer-limit enforcement and failure handling
5. Finish with Polish and quickstart validation

### Parallel Team Strategy

1. One engineer handles schema and shared backend foundations
2. One engineer handles tokenizer and indexing rules
3. One engineer handles documents/chat frontend flows after the backend contracts settle
4. Rejoin for US3 because failure semantics touch ingestion, retrieval, and UI together

---

## Notes

- `[P]` tasks touch different files and can run in parallel once dependencies are satisfied
- Every user story remains independently testable
- Tests must fail before implementation begins
- Keep `AiSearchService::askLlamaWithContext` as the canonical user-facing answer prompt throughout implementation
- Avoid mixing vectors, segment rows, or retrieval queries across incompatible model profiles
