# Implementation Plan: Grounded Chat Responses

**Branch**: `004-chat-semantic-answer` | **Date**: 2026-05-11 | **Spec**: [spec.md](/mnt/c/Workspace/spec-kit/AssistDoc/specs/004-chat-semantic-answer/spec.md)
**Input**: Feature specification from `/specs/004-chat-semantic-answer/spec.md`

## Summary

Deliver a tenant-scoped chat flow where an authenticated user submits a question, the backend retrieves the most relevant tenant knowledge from Qdrant, the answer is elaborated through `llama3.2`, and the final response includes visible source references or an explicit insufficient-information outcome. The implementation will reuse the existing `ChatController -> ChatService -> SemanticSearchService -> ChatCompletionService` path, preserve tenant isolation, and keep all user-facing text in Italian.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20)  
**Primary Dependencies**: Laravel API stack, Angular SPA, Bootstrap, ngx-formly, JWT authentication, Qdrant vector search, existing `SemanticSearchService`, existing `ChatCompletionService`, Ollama/Llama3.2 response generation  
**Storage**: Relational database for `chat_conversations`, `chat_messages`, and citations; Qdrant for tenant-scoped retrieval vectors; existing private document storage  
**Testing**: PHPUnit feature and unit tests, Angular component tests, Playwright end-to-end tests, OpenAPI contract review  
**Target Platform**: Linux-hosted web application with Angular frontend and Laravel backend  
**Project Type**: Web application with separate frontend and backend applications in one repository  
**Performance Goals**: 90% of chat questions return a completed answer or explicit insufficient-information response within 15 seconds; 95% of supported questions surface at least one citation; 100% of unsupported questions avoid unsupported answers  
**Constraints**: Stateless JWT auth; tenant isolation on every retrieval and response path; Italian-ready UI text; chat business logic in Services; frontend must consume backend APIs only; response generation must remain grounded in tenant evidence before elaboration; no foreign-tenant citations may leak into answers  
**Scale/Scope**: MVP chat flow for authenticated tenant users, sequential conversation history, cited answers, and fallback handling for weak or missing retrieval

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- Specification exists and contains no unresolved `[NEEDS CLARIFICATION]`.
- Planned scope traces to documented requirements and user stories.
- Architecture preserves REST API-first boundaries and does not bypass APIs.
- Backend design follows `Controller -> Service -> Repository -> Model`.
- Business rules, workflow transitions, and transaction boundaries live in Services.
- Contracts are identified for every API, including schemas, validation, auth, and error formats.
- Test-first coverage is planned before implementation starts: API success/failure, authorization, workflow transition, component, and critical Playwright E2E tests as applicable.
- Security design enforces stateless JWT auth, SSO outside local, and RBAC with endpoint-level role declarations.
- Frontend plan preserves Angular SPA module boundaries and Italian (`it-IT`) localization requirements.
- Any added complexity is explicitly justified with a simpler alternative and a rollback strategy.

## Project Structure

### Documentation (this feature)

```text
specs/004-chat-semantic-answer/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── chat-semantic-answer-openapi.yaml
└── tasks.md
```

### Source Code (repository root)

```text
backend/
├── app/
│   ├── Http/Controllers/Api/V1/ChatController.php
│   ├── Http/Requests/ChatMessageRequest.php
│   ├── Models/
│   │   ├── ChatConversation.php
│   │   ├── ChatMessage.php
│   │   └── MessageCitation.php
│   ├── Repositories/
│   │   ├── ChatConversationRepository.php
│   │   └── SearchQueryRepository.php
│   └── Services/
│       ├── AI/ChatCompletionService.php
│       ├── Chat/ChatService.php
│       ├── Chat/CitationService.php
│       └── Search/SemanticSearchService.php
├── routes/api.php
└── tests/
    ├── Feature/
    └── Unit/

frontend/
├── src/app/features/chat/
│   ├── chat-api.service.ts
│   ├── chat-page.component.ts
│   ├── chat-page.component.html
│   └── citation-panel.component.ts
└── tests/e2e/
    └── chat-mvp.spec.ts

specs/004-chat-semantic-answer/contracts/
└── chat-semantic-answer-openapi.yaml
```

**Structure Decision**: Use the existing Angular/Laravel split and extend the current chat feature slice instead of introducing a parallel assistant module. The backend work stays inside `backend/app/Services/Chat`, `backend/app/Services/Search`, and `backend/app/Services/AI`, while the frontend work stays inside `frontend/src/app/features/chat` and its related E2E coverage.

## Phase 0: Research Summary

- Reuse the current `ChatController -> ChatService` flow as the orchestration entry point, because the route and role boundaries already exist and only the response assembly needs to change.
- Resolve tenant-scoped supporting documents through `SemanticSearchService` first, then pass the top matches into the chat generation step so `llama3.2` can elaborate only on grounded evidence.
- Treat Qdrant as the retrieval layer and the relational chat tables as the source of truth for conversation history, messages, and citations.
- Preserve explicit insufficient-information handling when retrieval returns too little support, because the feature must avoid unsupported answers.
- Keep the frontend chat shell and citation panel, but wire them to real conversation and message data instead of hard-coded demo content.

## Phase 1: Design Outputs

- `research.md` captures the decisions and tradeoffs for retrieval-first chat orchestration, `llama3.2` response generation, tenant isolation, citation mapping, and fallback behavior.
- `data-model.md` defines conversations, messages, citations, logical question outcomes, and state transitions for the chat workflow.
- `contracts/chat-semantic-answer-openapi.yaml` defines the chat conversation and message endpoints, request/response schemas, authorization rules, and response-state semantics.
- Implemented response-state semantics use `answered`, `insufficient_information`, and `failed` for assistant messages and keep validation failures under `error.fieldErrors`.
- `quickstart.md` describes local setup, model availability, verification of cited answers, and the insufficient-information path.
- Agent context will be refreshed from this plan after the design files are written.

## Phase 2 Preview

- Derive dependency-ordered tasks that start with contract and failing backend tests, then chat orchestration, citation persistence, frontend binding, and E2E coverage for cited answers and fallback responses.

## Post-Design Constitution Check

- [x] Design still preserves REST-only frontend/backend interaction.
- [x] Service layer remains the owner of retrieval orchestration, answer generation, tenant enforcement, and audit emission.
- [x] Contracts cover the chat API surface before implementation.
- [x] Test-first work can start with backend API tests, unit tests, frontend component tests, and Playwright chat flows.
- [x] JWT, RBAC, tenant isolation, and Italian localization remain explicit in the design.
- [x] Added complexity is justified by the need to ground answers in tenant evidence and expose citations.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| None | N/A | N/A |
