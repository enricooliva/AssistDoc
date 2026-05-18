# Implementation Plan: Configured Qwen RAG Support

**Branch**: `006-qwen3-rag-support` | **Date**: 2026-05-14 | **Spec**: [spec.md](/home/enricooliva/Workspace/AssistDoc/specs/006-qwen3-rag-support/spec.md)
**Input**: Feature specification from `/specs/006-qwen3-rag-support/spec.md`

**Note**: This plan is aligned to the updated specification that replaces runtime chunk-profile CRUD with configuration-backed chunking profiles and makes document upload automatically start the configured chunking runs.

## Summary

Implement a configuration-backed RAG profile setup that uses `Qwen` as the default retrieval profile and at least three chunking profiles named `small`, `medium`, and `large`. Document upload must automatically start the configured chunking runs, while the system keeps tokenizer-aware chunking, preserves profile traceability, and continues grounded chat answers through the existing `askLlamaWithContext` response contract.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20)  
**Primary Dependencies**: Laravel API stack, Angular SPA, Bootstrap, ngx-formly, ngx-datatable, JWT authentication, Qdrant vector search, Ollama-hosted generation and embedding models  
**Storage**: PostgreSQL-style relational storage for profiles, documents, runs, and audit events; private file storage for uploaded source files; Qdrant for vector payloads  
**Testing**: PHPUnit feature/unit tests, Angular unit tests, Playwright end-to-end tests  
**Target Platform**: Linux containers orchestrated for local and small-scale deployment  
**Project Type**: Web application with Laravel backend and Angular frontend  
**Performance Goals**: Prepare documents safely under model-specific token limits without publishing incompatible chunks  
**Constraints**: API-first, stateless JWT auth, RBAC, centralized service-layer workflow control, Italian UI text, and auditability  
**Scale/Scope**: Multi-tenant document ingestion and retrieval with reusable profile configuration and historical traceability

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
specs/006-qwen3-rag-support/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
└── tasks.md
```

### Source Code (repository root)

```text
backend/
├── app/
│   ├── Http/
│   ├── Models/
│   ├── Repositories/
│   └── Services/
├── config/
├── database/
└── tests/

frontend/
├── src/
│   └── app/
└── tests/
```

**Structure Decision**: Use the existing Laravel backend and Angular frontend with profile logic in backend services, configuration/seeded profile definitions in `backend/config` and `backend/database/seeders`, contract documentation in `specs/006-qwen3-rag-support/contracts`, and front-end selection flows in `frontend/src/app/features`.

## Complexity Tracking

No constitution violations are expected. The main design choice is to keep retrieval and chunking profiles configuration-backed and consumed automatically by the upload workflow, rather than adding runtime CRUD endpoints or exposing selectors in the document-load path. That is the simpler approach because profile changes are deployment-driven and do not need operator-managed lifecycle screens.
