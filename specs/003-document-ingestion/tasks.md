# Tasks: Secure Document Ingestion

**Input**: Design documents from `/specs/003-document-ingestion/`
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
- Feature documentation lives under `specs/003-document-ingestion/`

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Align feature artifacts, placeholder tests, and document-slice scaffolding with the implementation plan

- [ ] T001 Update the document-ingestion contract artifact to match implementation entry points in `specs/003-document-ingestion/contracts/document-ingestion-openapi.yaml`
- [X] T002 [P] Replace placeholder document upload E2E assertions with ingestion-specific acceptance scaffolding in `frontend/tests/e2e/document-upload.spec.ts`
- [X] T003 [P] Create backend document feature test files for contract, workflow, and indexer coverage in `backend/tests/Feature/Documents/DocumentUploadTest.php`, `backend/tests/Feature/Documents/DocumentRetryTest.php`, and `backend/tests/Unit/Documents/DocumentIndexerServiceTest.php`
- [X] T004 [P] Create frontend document feature test files for Formly upload and document status coverage in `frontend/src/app/features/documents/document-upload.component.spec.ts` and `frontend/src/app/features/documents/document-list.component.spec.ts`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T005 [P] Add failing backend API route and authorization coverage for `GET /documents`, `POST /documents`, `GET /documents/{documentId}`, and `POST /documents/{documentId}/retry` in `backend/tests/Feature/ApiRoutesTest.php` and `backend/tests/Feature/Auth/AuthorizationTest.php`
- [ ] T006 [P] Add failing tenant-isolation coverage for document list/detail access in `backend/tests/Feature/Auth/TenantIsolationTest.php`
- [X] T007 [P] Expand the document request and response contract expectations for multipart upload and readiness fields in `backend/app/Http/Requests/DocumentUploadRequest.php`, `backend/app/Http/Requests/DocumentRetryRequest.php`, and `specs/003-document-ingestion/contracts/document-ingestion-openapi.yaml`
- [X] T008 [P] Extend the document persistence model for storage path, readiness timestamps, searchable segment counts, and replacement-safe linkage in `backend/app/Models/Document.php`, `backend/app/Models/DocumentSegment.php`, and `backend/app/Repositories/DocumentRepository.php`
- [X] T009 [P] Add repository support for document segment persistence, cleanup, and retrieval-readiness projections in `backend/app/Repositories/DocumentSegmentRepository.php` and `backend/app/Repositories/DocumentRepository.php`
- [X] T010 [P] Create the dedicated document indexing service skeleton based on the attachment pattern in `backend/app/Services/Documents/DocumentIndexerService.php`
- [X] T011 [P] Fix frontend document feature imports and shared Formly wiring for the upload slice in `frontend/src/app/features/documents/document-upload.component.ts`, `frontend/src/app/features/documents/documents-page.component.ts`, and `frontend/src/app/shared/dynamic-form/input-file/input-file.component.ts`

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Upload Documents Securely (Priority: P1) 🎯 MVP

**Goal**: Let an authorized tenant user upload a supported document through a Formly-based UI, store it securely, and see it appear in the tenant document workspace without cross-tenant exposure.

**Independent Test**: Sign in as an operator, upload a supported file from the documents page, verify the API returns `201`, confirm the document appears for the same tenant with an initial status, and confirm a viewer or another tenant cannot upload or access it.

### Tests for User Story 1 ⚠️

- [X] T012 [P] [US1] Add failing backend upload-success and upload-validation tests in `backend/tests/Feature/Documents/DocumentUploadTest.php`
- [ ] T013 [P] [US1] Add failing backend upload-authorization and cross-tenant detail tests in `backend/tests/Feature/Auth/AuthorizationTest.php` and `backend/tests/Feature/Auth/TenantIsolationTest.php`
- [X] T014 [P] [US1] Add failing Angular component tests for the Formly upload card, Italian validation, and disabled viewer state in `frontend/src/app/features/documents/document-upload.component.spec.ts`
- [ ] T015 [P] [US1] Add failing Playwright tests for operator upload and viewer rejection in `frontend/tests/e2e/document-upload.spec.ts`

### Implementation for User Story 1

- [X] T016 [P] [US1] Implement multipart upload validation rules and Italian-ready validation payloads in `backend/app/Http/Requests/DocumentUploadRequest.php` and `backend/app/Http/Controllers/Controller.php`
- [X] T017 [P] [US1] Implement secure file acceptance, storage-path persistence, and upload audit recording in `backend/app/Services/Documents/DocumentService.php` and `backend/app/Services/Audit/AuditService.php`
- [X] T018 [US1] Update the document controller and routes for multipart upload, document listing, and tenant-scoped detail responses in `backend/app/Http/Controllers/Api/V1/DocumentController.php` and `backend/routes/api.php`
- [X] T019 [P] [US1] Refactor the shared Formly file field for actual `File` handling, Bootstrap Italia classes, and explicit reset behavior in `frontend/src/app/shared/dynamic-form/input-file/input-file.component.ts`
- [X] T020 [P] [US1] Implement the document upload API client and request/response models for multipart submission in `frontend/src/app/features/documents/document-api.service.ts` and `frontend/src/app/features/documents/document.models.ts`
- [X] T021 [US1] Replace the placeholder upload UI with a Formly-based upload form and fix its standalone imports in `frontend/src/app/features/documents/document-upload.component.ts` and `frontend/src/app/features/documents/documents-page.component.ts`
- [X] T022 [US1] Update the document list and status badge to show accepted and queued upload states with Italian messaging in `frontend/src/app/features/documents/document-list.component.ts` and `frontend/src/app/features/documents/document-status-badge.component.ts`

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Prepare Documents for Retrieval (Priority: P2)

**Goal**: Let operators track document processing through queued, processing, ready, and failed states while the backend extracts text into searchable chunks that remain traceable to the source document.

**Independent Test**: Upload a supported file, run document processing, verify status transitions and chunk persistence in `document_segments`, and confirm failed files remain non-ready with a user-readable reason and a retry path.

### Tests for User Story 2 ⚠️

- [X] T023 [P] [US2] Add failing backend lifecycle and retry eligibility tests in `backend/tests/Feature/Documents/DocumentRetryTest.php`
- [X] T024 [P] [US2] Add failing backend processing-service workflow tests for `queued -> processing -> ready|failed` in `backend/tests/Unit/Documents/DocumentProcessingServiceTest.php`
- [X] T025 [P] [US2] Add failing Angular component tests for status display, failure messaging, and retry affordances in `frontend/src/app/features/documents/document-list.component.spec.ts`
- [ ] T026 [P] [US2] Extend Playwright coverage for failed processing and retry transitions in `frontend/tests/e2e/document-upload.spec.ts`

### Implementation for User Story 2

- [X] T027 [P] [US2] Implement document segment persistence and cleanup methods for processing runs in `backend/app/Repositories/DocumentSegmentRepository.php`
- [X] T028 [P] [US2] Implement document list/detail projections with readiness timestamps, failure reasons, and segment counts in `backend/app/Repositories/DocumentRepository.php`
- [X] T029 [US2] Implement document workflow orchestration, retry handling, and state-transition auditing in `backend/app/Services/Documents/DocumentProcessingService.php` and `backend/app/Services/Documents/DocumentService.php`
- [X] T030 [US2] Implement chunk extraction, normalization, and source-to-segment traceability in `backend/app/Services/Documents/DocumentIndexerService.php`
- [X] T031 [US2] Update document API responses for processing, ready, and failed states in `backend/app/Http/Controllers/Api/V1/DocumentController.php` and `backend/app/Http/Requests/DocumentRetryRequest.php`
- [X] T032 [US2] Implement frontend document polling or refresh behavior, failure-state messaging, and retry submission in `frontend/src/app/features/documents/document-api.service.ts`, `frontend/src/app/features/documents/document-list.component.ts`, and `frontend/src/app/features/documents/document-upload.component.ts`

**Checkpoint**: At this point, User Stories 1 and 2 should both work independently

---

## Phase 5: User Story 3 - Generate Embedding-Ready Knowledge Records (Priority: P3)

**Goal**: Ensure every retained searchable chunk receives an embedding and Qdrant index record before the document becomes retrieval-ready, while stale vectors are removed on retry or replacement-style reprocessing.

**Independent Test**: Process a document to completion, confirm each persisted segment has embedding metadata and a Qdrant point, and verify a failed or retried run never leaves stale searchable vectors active.

### Tests for User Story 3 ⚠️

- [X] T033 [P] [US3] Add failing backend indexer tests for chunk embeddings, empty-content handling, and vector payload generation in `backend/tests/Unit/Documents/DocumentIndexerServiceTest.php`
- [X] T034 [P] [US3] Add failing backend consistency tests for ready-state gating and stale-vector cleanup on retry in `backend/tests/Unit/Documents/DocumentProcessingServiceTest.php`
- [X] T035 [P] [US3] Add failing backend retrieval-readiness integration coverage in `backend/tests/Feature/Documents/DocumentUploadTest.php`
- [ ] T036 [P] [US3] Extend Playwright verification for retrieval-ready status visibility after successful ingestion in `frontend/tests/e2e/document-upload.spec.ts`

### Implementation for User Story 3

- [X] T037 [P] [US3] Implement embedding generation, Qdrant upsert payloads, and deterministic point identifiers in `backend/app/Services/Documents/DocumentIndexerService.php`
- [X] T038 [P] [US3] Implement Qdrant cleanup and stale-index withdrawal during retry or reprocessing in `backend/app/Services/Documents/DocumentIndexerService.php` and `backend/app/Services/Documents/DocumentProcessingService.php`
- [X] T039 [US3] Gate the `ready` transition on successful segment persistence plus embedding writes and expose readiness metadata to downstream consumers in `backend/app/Services/Documents/DocumentProcessingService.php` and `backend/app/Repositories/DocumentSegmentRepository.php`
- [X] T040 [US3] Update semantic retrieval integration to ignore non-ready documents and consume the new document segment payload shape in `backend/app/Services/Search/SemanticSearchService.php`
- [X] T041 [US3] Surface retrieval-ready counts and final success states in the documents UI in `frontend/src/app/features/documents/document.models.ts`, `frontend/src/app/features/documents/document-list.component.ts`, and `frontend/src/app/features/documents/document-status-badge.component.ts`

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [X] T042 [P] Update quickstart verification and implementation notes to match the final ingestion flow in `specs/003-document-ingestion/quickstart.md`
- [X] T043 [P] Align the feature plan and data model with any implementation-driven naming adjustments in `specs/003-document-ingestion/plan.md` and `specs/003-document-ingestion/data-model.md`
- [ ] T044 [P] Add cross-cutting regression coverage for document authorization, upload validation, and retrieval-readiness invariants in `backend/tests/Feature/`, `backend/tests/Unit/Documents/`, and `frontend/tests/e2e/document-upload.spec.ts`
- [ ] T045 Verify Italian localization coverage and Bootstrap Italia consistency across the documents experience in `frontend/src/app/features/documents/` and `frontend/src/assets/i18n/it-IT.json`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phases 3-5)**: All depend on Foundational phase completion
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Phase 2 and is the MVP slice for secure upload
- **User Story 2 (P2)**: Starts after Phase 2 and builds on the persisted document created in US1
- **User Story 3 (P3)**: Starts after Phase 2 and builds on the chunk persistence and workflow orchestration created in US2

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Request validation and contracts before controller and UI wiring
- Persistence updates before service orchestration
- Service orchestration before frontend status integration
- Retrieval-ready gating before semantic retrieval integration

### Parallel Opportunities

- Setup tasks `T002-T004` can run in parallel
- Foundational tasks `T005-T011` can run in parallel across backend and frontend workstreams
- In US1, tests `T012-T015`, backend tasks `T016-T018`, and frontend tasks `T019-T020` can run in parallel inside their dependency bands
- In US2, tests `T023-T026`, repository tasks `T027-T028`, and frontend refresh task `T032` can run in parallel once the US1 baseline exists
- In US3, tests `T033-T036` and backend indexing tasks `T037-T038` can run in parallel before final integration tasks `T039-T041`

---

## Parallel Example: User Story 1

```bash
# Launch US1 failing tests together
Task: "T012 [US1] backend upload-success and validation tests in backend/tests/Feature/Documents/DocumentUploadTest.php"
Task: "T013 [US1] backend upload-authorization and tenant-isolation tests in backend/tests/Feature/Auth/AuthorizationTest.php and backend/tests/Feature/Auth/TenantIsolationTest.php"
Task: "T014 [US1] Angular Formly upload component tests in frontend/src/app/features/documents/document-upload.component.spec.ts"
Task: "T015 [US1] Playwright operator/viewer upload flow in frontend/tests/e2e/document-upload.spec.ts"

# Launch US1 backend and frontend build-out in parallel after tests exist
Task: "T016 [US1] multipart upload validation in backend/app/Http/Requests/DocumentUploadRequest.php"
Task: "T017 [US1] secure storage orchestration in backend/app/Services/Documents/DocumentService.php"
Task: "T019 [US1] shared Formly file field refactor in frontend/src/app/shared/dynamic-form/input-file/input-file.component.ts"
Task: "T020 [US1] document upload API client in frontend/src/app/features/documents/document-api.service.ts"
```

---

## Parallel Example: User Story 2

```bash
# Launch US2 failing tests together
Task: "T023 [US2] backend retry lifecycle tests in backend/tests/Feature/Documents/DocumentRetryTest.php"
Task: "T024 [US2] backend processing workflow tests in backend/tests/Unit/Documents/DocumentProcessingServiceTest.php"
Task: "T025 [US2] Angular status and retry component tests in frontend/src/app/features/documents/document-list.component.spec.ts"
Task: "T026 [US2] Playwright failed-processing and retry flow in frontend/tests/e2e/document-upload.spec.ts"

# Launch US2 persistence and orchestration work in parallel after tests exist
Task: "T027 [US2] document segment repository methods in backend/app/Repositories/DocumentSegmentRepository.php"
Task: "T028 [US2] document detail projections in backend/app/Repositories/DocumentRepository.php"
Task: "T030 [US2] chunk extraction and traceability in backend/app/Services/Documents/DocumentIndexerService.php"
Task: "T032 [US2] frontend refresh and retry wiring in frontend/src/app/features/documents/"
```

---

## Parallel Example: User Story 3

```bash
# Launch US3 failing tests together
Task: "T033 [US3] backend indexer embedding tests in backend/tests/Unit/Documents/DocumentIndexerServiceTest.php"
Task: "T034 [US3] backend ready-state and stale-vector tests in backend/tests/Unit/Documents/DocumentProcessingServiceTest.php"
Task: "T035 [US3] backend retrieval-readiness feature coverage in backend/tests/Feature/Documents/DocumentUploadTest.php"
Task: "T036 [US3] Playwright ready-state visibility flow in frontend/tests/e2e/document-upload.spec.ts"

# Launch US3 indexing work in parallel after tests exist
Task: "T037 [US3] Qdrant upsert and point-id logic in backend/app/Services/Documents/DocumentIndexerService.php"
Task: "T038 [US3] stale-index cleanup in backend/app/Services/Documents/DocumentProcessingService.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Confirm secure operator upload, tenant isolation, and frontend import stability
5. Demo the upload slice before moving into indexing complexity

### Incremental Delivery

1. Setup + Foundational establish contracts, persistence shape, indexer skeleton, and frontend import wiring
2. Add User Story 1 for secure multipart upload and tenant-scoped visibility
3. Add User Story 2 for chunk preparation, failure handling, and retry workflow
4. Add User Story 3 for embedding generation, Qdrant synchronization, and retrieval-readiness guarantees
5. Finish with polish, documentation sync, and regression hardening

### Parallel Team Strategy

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: US1 backend upload contract and storage
   - Developer B: US1 frontend Formly upload and import fixes
   - Developer C: Foundational indexer skeleton and repository groundwork
3. After US1 lands, split US2 and US3 across workflow and indexing workstreams

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] labels map tasks to specific user stories for traceability
- Every story remains independently testable at its checkpoint
- The frontend import fix requested by the user is explicitly covered by `T011` and `T021`
- Verify tests fail before implementing
