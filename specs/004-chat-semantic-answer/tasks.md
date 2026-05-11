# Tasks: Grounded Chat Responses

**Input**: Design documents from `/specs/004-chat-semantic-answer/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are MANDATORY. Generate test tasks before implementation tasks for every applicable story and shared foundation.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this belongs to (e.g. `US1`, `US2`, `US3`)
- Include exact file paths in descriptions

## Path Conventions

- Backend application lives under `backend/`
- Frontend application lives under `frontend/`
- Feature documentation lives under `specs/004-chat-semantic-answer/`

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Align the feature artifacts, test scaffolding, and chat slice entry points with the implementation plan

- [X] T001 Refresh grounded-chat response examples and outcome-state notes in `specs/004-chat-semantic-answer/contracts/chat-semantic-answer-openapi.yaml`
- [X] T002 [P] Create backend chat feature test skeletons in `backend/tests/Feature/Chat/ConversationListTest.php`, `backend/tests/Feature/Chat/ConversationShowTest.php`, and `backend/tests/Feature/Chat/SubmitChatMessageTest.php`
- [X] T003 [P] Create backend chat unit test skeletons in `backend/tests/Unit/Chat/ChatServiceTest.php`, `backend/tests/Unit/Chat/CitationServiceTest.php`, and `backend/tests/Unit/AI/ChatCompletionServiceTest.php`
- [X] T004 [P] Expand frontend chat component test scaffolding in `frontend/src/app/features/chat/chat-page.component.spec.ts` and `frontend/src/app/features/chat/citation-panel.component.spec.ts`
- [X] T005 [P] Replace placeholder chat E2E expectations with grounded-chat scaffolding in `frontend/tests/e2e/chat-mvp.spec.ts`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T006 [P] Add failing API route and RBAC coverage for `GET /chat/conversations`, `POST /chat/conversations`, `GET /chat/conversations/{conversationId}`, and `POST /chat/conversations/{conversationId}/messages` in `backend/tests/Feature/ApiRoutesTest.php` and `backend/tests/Feature/Auth/AuthorizationTest.php`
- [X] T007 [P] Add failing tenant-isolation coverage for chat conversation, message, and citation access in `backend/tests/Feature/Auth/TenantIsolationTest.php`
- [X] T008 [P] Define grounded-chat request validation and contract alignment in `backend/app/Http/Requests/CreateChatConversationRequest.php`, `backend/app/Http/Requests/ChatMessageRequest.php`, and `specs/004-chat-semantic-answer/contracts/chat-semantic-answer-openapi.yaml`
- [X] T009 [P] Extend chat persistence fields and relationships for thread ordering, response states, and citations in `backend/app/Models/ChatConversation.php`, `backend/app/Models/ChatMessage.php`, and `backend/app/Models/MessageCitation.php`
- [X] T010 [P] Add conversation and citation persistence helpers in `backend/app/Repositories/ChatConversationRepository.php` and `backend/app/Repositories/MessageCitationRepository.php`
- [X] T011 [P] Add chat response DTOs for thread and exchange payload serialization in `backend/app/DataTransferObjects/Chat/ChatConversationData.php`, `backend/app/DataTransferObjects/Chat/ChatMessageData.php`, and `backend/app/DataTransferObjects/Chat/ChatExchangeData.php`
- [X] T012 [P] Add audit-event support for chat question outcomes in `backend/app/Services/Audit/AuditService.php`, `backend/app/Repositories/AuditEventRepository.php`, and `backend/app/Models/AuditEvent.php`
- [X] T013 [P] Create typed frontend chat request and response models in `frontend/src/app/features/chat/chat.models.ts` and `frontend/src/app/features/chat/chat-api.service.ts`

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Ask a Grounded Question (Priority: P1) 🎯 MVP

**Goal**: Let an authenticated tenant user ask a question, receive an Italian answer grounded in tenant knowledge, and continue the same conversation with follow-up questions.

**Independent Test**: Sign in as a tenant user with indexed tenant documents, create or open a conversation, submit a question, confirm the answer is grounded in that tenant's evidence only, and then submit a follow-up question that remains in the same thread.

### Tests for User Story 1 ⚠️

- [X] T014 [P] [US1] Add failing backend feature tests for grounded answer creation and follow-up questions in `backend/tests/Feature/Chat/SubmitChatMessageTest.php` and `backend/tests/Feature/Chat/ConversationShowTest.php`
- [X] T015 [P] [US1] Add failing backend orchestration tests for retrieval-first tenant-scoped answering in `backend/tests/Unit/Chat/ChatServiceTest.php`
- [X] T016 [P] [US1] Add failing Angular component tests for question submission, loading state, and chronological message rendering in `frontend/src/app/features/chat/chat-page.component.spec.ts`
- [X] T017 [P] [US1] Add failing Playwright coverage for first-question and follow-up chat flows in `frontend/tests/e2e/chat-mvp.spec.ts`

### Implementation for User Story 1

- [X] T018 [P] [US1] Implement tenant-scoped conversation listing, default-title creation, and ordered thread retrieval in `backend/app/Repositories/ChatConversationRepository.php` and `backend/app/Models/ChatConversation.php`
- [X] T019 [P] [US1] Implement retrieval-first chat orchestration and question/answer persistence in `backend/app/Services/Chat/ChatService.php` and `backend/app/Repositories/SearchQueryRepository.php`
- [X] T020 [P] [US1] Implement grounded prompt assembly and `llama3.2` completion handling in `backend/app/Services/AI/ChatCompletionService.php`
- [X] T021 [US1] Implement tenant-filtered semantic retrieval and evidence normalization for chat answers in `backend/app/Services/Search/SemanticSearchService.php`
- [X] T022 [US1] Implement conversation create/read/question endpoints and validation wiring in `backend/app/Http/Controllers/Api/V1/ChatController.php`, `backend/routes/api.php`, `backend/app/Http/Requests/CreateChatConversationRequest.php`, and `backend/app/Http/Requests/ChatMessageRequest.php`
- [X] T023 [P] [US1] Implement frontend chat API integration and thread payload mapping in `frontend/src/app/features/chat/chat-api.service.ts` and `frontend/src/app/features/chat/chat.models.ts`
- [X] T024 [US1] Replace placeholder chat UI with an API-backed composer, loading feedback, and Italian validation text in `frontend/src/app/features/chat/chat-page.component.ts`, `frontend/src/app/features/chat/chat-page.component.html`, and `frontend/src/app/features/chat/chat-page.component.scss`

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Inspect Supporting Evidence (Priority: P2)

**Goal**: Let a tenant user inspect the supporting references behind each grounded answer directly from the chat experience.

**Independent Test**: Submit a question with relevant tenant support, confirm the assistant answer contains visible references, and verify each reference identifies the supporting tenant document and quoted passage clearly enough to inspect.

### Tests for User Story 2 ⚠️

- [X] T025 [P] [US2] Add failing backend feature tests for cited answer payloads and thread citation visibility in `backend/tests/Feature/Chat/SubmitChatMessageTest.php` and `backend/tests/Feature/Chat/ConversationShowTest.php`
- [X] T026 [P] [US2] Add failing backend unit tests for citation mapping, source labels, and quote extraction in `backend/tests/Unit/Chat/CitationServiceTest.php`
- [X] T027 [P] [US2] Add failing Angular component tests for citation-panel rendering and answer-linked references in `frontend/src/app/features/chat/citation-panel.component.spec.ts` and `frontend/src/app/features/chat/chat-page.component.spec.ts`
- [X] T028 [P] [US2] Extend Playwright coverage for citation inspection in `frontend/tests/e2e/chat-mvp.spec.ts`

### Implementation for User Story 2

- [X] T029 [P] [US2] Implement citation persistence helpers and tenant-safe citation lookups in `backend/app/Repositories/MessageCitationRepository.php` and `backend/app/Models/MessageCitation.php`
- [X] T030 [P] [US2] Implement retrieval-to-citation mapping with source labels and quoted excerpts in `backend/app/Services/Chat/CitationService.php`
- [X] T031 [P] [US2] Implement citation-aware thread and exchange serializers in `backend/app/DataTransferObjects/Chat/ChatMessageData.php` and `backend/app/DataTransferObjects/Chat/ChatExchangeData.php`
- [X] T032 [US2] Expose supporting references from the chat service and controller responses in `backend/app/Services/Chat/ChatService.php` and `backend/app/Http/Controllers/Api/V1/ChatController.php`
- [X] T033 [P] [US2] Implement typed citation models and standalone citation-panel bindings in `frontend/src/app/features/chat/chat.models.ts`, `frontend/src/app/features/chat/citation-panel.component.ts`, and `frontend/src/app/features/chat/citation-panel.component.spec.ts`
- [X] T034 [US2] Integrate visible supporting-reference UI into the chat thread and answer area in `frontend/src/app/features/chat/chat-page.component.ts`, `frontend/src/app/features/chat/chat-page.component.html`, and `frontend/src/app/features/chat/chat-page.component.scss`

**Checkpoint**: At this point, User Stories 1 and 2 should both work independently

---

## Phase 5: User Story 3 - Handle Missing Evidence Safely (Priority: P3)

**Goal**: Return explicit insufficient-information or failure outcomes when the tenant evidence is weak, missing, or the answering flow cannot complete safely.

**Independent Test**: Ask a question with no adequate tenant support, confirm the response declines to answer with an explicit Italian insufficient-information message, and verify a backend processing failure returns a user-readable failed outcome without leaking foreign tenant content.

### Tests for User Story 3 ⚠️

- [X] T035 [P] [US3] Add failing backend feature tests for insufficient-information and failed chat outcomes in `backend/tests/Feature/Chat/SubmitChatMessageTest.php` and `backend/tests/Feature/ApiRoutesTest.php`
- [X] T036 [P] [US3] Add failing backend unit tests for weak-support thresholds and completion-failure fallback in `backend/tests/Unit/Chat/ChatServiceTest.php` and `backend/tests/Unit/AI/ChatCompletionServiceTest.php`
- [X] T037 [P] [US3] Add failing Angular component tests for insufficient-information messaging, failed states, and submit disabling in `frontend/src/app/features/chat/chat-page.component.spec.ts`
- [X] T038 [P] [US3] Extend Playwright coverage for unsupported-question decline and user-readable failure outcomes in `frontend/tests/e2e/chat-mvp.spec.ts`

### Implementation for User Story 3

- [X] T039 [P] [US3] Implement evidence-strength evaluation and insufficient-information branching in `backend/app/Services/Chat/ChatService.php` and `backend/app/Services/Search/SemanticSearchService.php`
- [X] T040 [P] [US3] Implement failed-outcome persistence and audit recording for chat questions in `backend/app/Services/Chat/ChatService.php` and `backend/app/Services/Audit/AuditService.php`
- [X] T041 [US3] Extend outcome-state contract details and Italian validation messaging for unsupported questions and failures in `specs/004-chat-semantic-answer/contracts/chat-semantic-answer-openapi.yaml`, `backend/app/Http/Requests/ChatMessageRequest.php`, and `backend/app/Http/Controllers/Controller.php`
- [X] T042 [US3] Implement Italian insufficient-information and failure UI states in `frontend/src/app/features/chat/chat-api.service.ts`, `frontend/src/app/features/chat/chat-page.component.ts`, `frontend/src/app/features/chat/chat-page.component.html`, and `frontend/src/app/features/chat/chat-page.component.scss`

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [X] T043 [P] Update chat verification steps and operator test data notes in `specs/004-chat-semantic-answer/quickstart.md`
- [X] T044 [P] Sync implementation-driven naming and state semantics in `specs/004-chat-semantic-answer/plan.md` and `specs/004-chat-semantic-answer/data-model.md`
- [X] T045 [P] Add cross-cutting regression coverage for chat authorization, citation visibility, and fallback invariants in `backend/tests/Feature/Auth/AuthorizationTest.php`, `backend/tests/Feature/Auth/TenantIsolationTest.php`, and `frontend/tests/e2e/chat-mvp.spec.ts`
- [X] T046 Verify Italian chat copy, loading states, and citation UX consistency in `frontend/src/app/features/chat/chat-page.component.ts`, `frontend/src/app/features/chat/chat-page.component.html`, and `frontend/src/app/features/chat/citation-panel.component.ts`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phases 3-5)**: All depend on Foundational phase completion
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Phase 2 and delivers the MVP grounded question-and-answer flow
- **User Story 2 (P2)**: Starts after Phase 2 and builds on the answer payload created in US1 to expose visible evidence
- **User Story 3 (P3)**: Starts after Phase 2 and builds on the US1 answer flow to add safe fallback and failure outcomes

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Persistence and serialization changes before controller payload completion
- Backend orchestration before frontend consumption
- API integration before UI polish
- Audit and fallback logic before cross-cutting regression hardening

### Parallel Opportunities

- Setup tasks `T002-T005` can run in parallel
- Foundational tasks `T006-T013` can run in parallel across backend, contract, audit, and frontend modeling workstreams
- In US1, tests `T014-T017`, backend build-out `T018-T020`, and frontend API work `T023` can run in parallel inside their dependency bands
- In US2, tests `T025-T028`, backend citation tasks `T029-T031`, and frontend citation-model work `T033` can run in parallel once US1 is stable
- In US3, tests `T035-T038` and backend fallback tasks `T039-T040` can run in parallel before final contract and UI integration `T041-T042`

---

## Parallel Example: User Story 1

```bash
# Launch US1 failing tests together
Task: "T014 [US1] grounded-answer and follow-up feature tests in backend/tests/Feature/Chat/SubmitChatMessageTest.php and backend/tests/Feature/Chat/ConversationShowTest.php"
Task: "T015 [US1] retrieval-first orchestration tests in backend/tests/Unit/Chat/ChatServiceTest.php"
Task: "T016 [US1] Angular submit/loading/thread tests in frontend/src/app/features/chat/chat-page.component.spec.ts"
Task: "T017 [US1] Playwright first-question and follow-up flow in frontend/tests/e2e/chat-mvp.spec.ts"

# Launch US1 implementation tasks after tests exist
Task: "T018 [US1] conversation repository and ordering logic in backend/app/Repositories/ChatConversationRepository.php"
Task: "T019 [US1] chat orchestration in backend/app/Services/Chat/ChatService.php"
Task: "T020 [US1] llama3.2 completion handling in backend/app/Services/AI/ChatCompletionService.php"
Task: "T023 [US1] frontend API mapping in frontend/src/app/features/chat/chat-api.service.ts"
```

---

## Parallel Example: User Story 2

```bash
# Launch US2 failing tests together
Task: "T025 [US2] cited-answer feature tests in backend/tests/Feature/Chat/SubmitChatMessageTest.php and backend/tests/Feature/Chat/ConversationShowTest.php"
Task: "T026 [US2] citation mapping tests in backend/tests/Unit/Chat/CitationServiceTest.php"
Task: "T027 [US2] Angular citation-panel tests in frontend/src/app/features/chat/citation-panel.component.spec.ts"
Task: "T028 [US2] Playwright citation inspection flow in frontend/tests/e2e/chat-mvp.spec.ts"

# Launch US2 implementation tasks after tests exist
Task: "T029 [US2] citation repository helpers in backend/app/Repositories/MessageCitationRepository.php"
Task: "T030 [US2] citation mapping in backend/app/Services/Chat/CitationService.php"
Task: "T031 [US2] citation-aware DTO serialization in backend/app/DataTransferObjects/Chat/ChatExchangeData.php"
Task: "T033 [US2] citation-panel bindings in frontend/src/app/features/chat/citation-panel.component.ts"
```

---

## Parallel Example: User Story 3

```bash
# Launch US3 failing tests together
Task: "T035 [US3] insufficient-information and failed-outcome feature tests in backend/tests/Feature/Chat/SubmitChatMessageTest.php"
Task: "T036 [US3] weak-support and completion-failure unit tests in backend/tests/Unit/Chat/ChatServiceTest.php and backend/tests/Unit/AI/ChatCompletionServiceTest.php"
Task: "T037 [US3] Angular fallback-state tests in frontend/src/app/features/chat/chat-page.component.spec.ts"
Task: "T038 [US3] Playwright unsupported-question decline flow in frontend/tests/e2e/chat-mvp.spec.ts"

# Launch US3 implementation tasks after tests exist
Task: "T039 [US3] evidence-strength branching in backend/app/Services/Chat/ChatService.php"
Task: "T040 [US3] failed-outcome auditing in backend/app/Services/Audit/AuditService.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Confirm grounded tenant-only answers, follow-up thread continuity, and Italian loading/validation behavior
5. Demo the chat MVP before adding citations and fallback refinements

### Incremental Delivery

1. Setup + Foundational establish contracts, models, repositories, audit hooks, and typed frontend payloads
2. Add User Story 1 for grounded tenant-scoped chat answers and conversation continuity
3. Add User Story 2 for visible supporting references and citation inspection
4. Add User Story 3 for insufficient-information handling and user-readable failures
5. Finish with documentation sync, regression hardening, and final Italian UX review

### Parallel Team Strategy

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: US1 backend orchestration and API contracts
   - Developer B: US1 frontend chat integration and UI
   - Developer C: Shared audit, DTO, and repository groundwork
3. After US1 lands, split US2 citation work and US3 fallback work into parallel backend/frontend tracks

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] labels map tasks to specific user stories for traceability
- Every story includes explicit backend, frontend, and E2E validation work
- `CreateChatConversationRequest.php`, `MessageCitationRepository.php`, `chat.models.ts`, and citation component specs are expected additions in this feature
- Verify tests fail before implementing each story
