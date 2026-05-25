# Implementation Plan: Secure Document Ingestion

**Branch**: `003-document-ingestion` | **Date**: 2026-05-11 | **Spec**: [spec.md](/mnt/c/Workspace/spec-kit/AssistDoc/specs/003-document-ingestion/spec.md)
**Input**: Feature specification from `/specs/003-document-ingestion/spec.md` plus planning note: "Use an ngx-formly custom file input component (reuse if available, otherwise create one) for document upload, ensuring validation and Bootstrap Italia consistency. For indexing, implement a DocumentIndexerService based on the existing attachmentIndexerService pattern to handle chunk embedding and storage in Qdrant."

## Summary

Deliver the document-ingestion slice by replacing the placeholder upload UI with an Angular Formly-based upload flow that reuses the existing shared file field, aligned to Bootstrap Italia validation and feedback patterns, and by extending the Laravel document pipeline with a dedicated `DocumentIndexerService` modeled on the current `AttachmentIndexerService`. The implementation will keep the existing `DocumentController -> DocumentService -> Repository -> Model` boundaries, upgrade the document upload contract to real multipart ingestion, extract readable text from uploaded PDFs before chunking, persist searchable segments in the relational model, write embeddings to Qdrant, and expose retrieval-readiness state to downstream search and chat features.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), YAML for API contracts  
**Primary Dependencies**: Laravel API stack, Angular SPA, Bootstrap Italia styling on top of Bootstrap, ngx-formly, JWT authentication, Laravel queue workers, Qdrant vector search, existing `EmbeddingService` configured for Ollama `mxbai-embed-large` (1024 dimensions) at `http://192.168.5.137:11434/api/embed` with shared input truncation before embedding, existing `AttachmentIndexerService` chunking pattern, an internal PDF text extractor for uploaded binary streams  
**Storage**: Relational database for `documents` and `document_segments`, private file storage for uploaded source files, Qdrant for semantic retrieval vectors  
**Testing**: PHPUnit feature and unit tests, Angular component tests, Playwright end-to-end tests, OpenAPI contract review  
**Target Platform**: Linux-hosted web application with Angular frontend and Laravel API backend  
**Project Type**: Web application with separate `frontend/` and `backend/` applications in one repository  
**Performance Goals**: Preserve the spec targets of 90% upload initiation under 2 minutes, 95% retrieval-ready completion under 10 minutes, and 100% ready documents having segment and embedding coverage  
**Constraints**: Stateless JWT auth; RBAC on every document endpoint; Italian-ready UI text; frontend must consume backend APIs only; business rules and workflow transitions stay in Services; upload flow must reuse shared Formly infrastructure where possible; indexing must remain asynchronous; Qdrant and relational segment data must stay consistent enough that non-ready documents never become searchable  
**Scale/Scope**: Single feature slice covering document upload, replacement-ready ingestion workflow, chunk persistence, embedding persistence, document readiness visibility, and tests for the documents area only

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] Specification exists and contains no unresolved `[NEEDS CLARIFICATION]`.
- [x] Planned scope traces to documented requirements and user stories.
- [x] Architecture preserves REST API-first boundaries and does not bypass APIs.
- [x] Backend design follows `Controller -> Service -> Repository -> Model`.
- [x] Business rules, workflow transitions, and transaction boundaries live in Services.
- [x] Contracts are identified for every API, including schemas, validation, auth, and error formats.
- [x] Test-first coverage is planned before implementation starts: API success/failure, authorization, workflow transition, component, and critical Playwright E2E tests.
- [x] Security design enforces stateless JWT auth, SSO outside local, and RBAC with endpoint-level role declarations.
- [x] Frontend plan preserves Angular SPA module boundaries and Italian (`it-IT`) localization requirements.
- [x] Any added complexity is explicitly justified with a simpler alternative and a rollback strategy.

## Project Structure

### Documentation (this feature)

```text
specs/003-document-ingestion/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── document-ingestion-openapi.yaml
└── tasks.md
```

### Source Code (repository root)

```text
backend/
├── app/
│   ├── Http/Controllers/Api/V1/
│   │   └── DocumentController.php
│   ├── Http/Requests/
│   │   ├── DocumentRetryRequest.php
│   │   └── DocumentUploadRequest.php
│   ├── Models/
│   │   ├── Document.php
│   │   └── DocumentSegment.php
│   ├── Repositories/
│   │   ├── DocumentRepository.php
│   │   └── DocumentSegmentRepository.php
│   └── Services/
│       ├── AI/EmbeddingService.php
│       ├── Documents/
│       │   ├── AttachmentIndexerService.php
│       │   ├── DocumentIndexerService.php
│       │   ├── DocumentProcessingService.php
│       │   └── DocumentService.php
│       └── QdrantService.php
├── routes/
│   └── api.php
└── tests/
    ├── Feature/
    └── Unit/

frontend/
├── src/app/
│   ├── features/documents/
│   │   ├── document-api.service.ts
│   │   ├── document-list.component.ts
│   │   ├── document-upload.component.ts
│   │   └── documents-page.component.ts
│   └── shared/dynamic-form/
│       └── input-file/input-file.component.ts
└── tests/e2e/
    └── document-upload.spec.ts
```

**Structure Decision**: Use the existing split `backend/` and `frontend/` web-application structure. The feature lands in the current `features/documents` frontend slice and the current `Services/Documents` backend slice, avoiding a parallel ingestion module while still introducing a dedicated `DocumentIndexerService` under the document domain.

## Phase 0: Research Summary

- Reuse the existing shared Formly `input-file` component as the base upload field instead of creating a second file selector, but harden it to work with actual `File` values, Italian validation messages, and Bootstrap Italia-compatible markup and states.
- Replace the placeholder template-driven document upload card with a Formly form that maps one visible file input to the actual multipart upload contract and keeps transport logic in `DocumentApiService`.
- Introduce a dedicated `DocumentIndexerService` in `App\Services\Documents` by extracting the durable chunking, normalization, embedding, and Qdrant-upsert concepts from `AttachmentIndexerService`, while removing attachment-specific payload assumptions.
- Add a lightweight `PdfTextExtractorService` so uploaded PDFs are parsed into text before normalization, segmentation, and embedding, without depending on an external system binary.
- Keep workflow orchestration in `DocumentProcessingService`, which becomes responsible for state transitions, audit events, indexer invocation, and failure handling rather than embedding inline logic in controllers or repositories.
- Persist chunk metadata in `document_segments` as the relational source of truth and use Qdrant as the semantic retrieval index keyed back to tenant, document, and segment identity.
- Evolve the `/api/v1/documents` contract to accept multipart file upload plus metadata, retain `/retry`, and add explicit document detail fields required for status and failure display without expanding into downstream retrieval endpoints.

## Phase 1: Design Outputs

- `research.md` records the decisions on Formly reuse, Bootstrap Italia alignment, multipart upload contract, indexing boundaries, chunk strategy, and consistency rules between database records and Qdrant vectors.
- `data-model.md` defines the document, ingestion job semantics, and segment entities, including validations, state transitions, and source-to-chunk traceability.
- `contracts/document-ingestion-openapi.yaml` captures the document upload, list, detail, retry, and replacement-ready workflow contracts with auth, validation, and error rules.
- `quickstart.md` defines local verification steps for uploading a document through the Formly UI, observing queued and ready states, and confirming relational segment plus Qdrant indexing outputs backed by `mxbai-embed-large` vectors.
- Agent context will be refreshed after these design files are written.

## Phase 2 Preview

- Derive dependency-ordered tasks starting with contract and failing tests, then backend multipart upload persistence, workflow and indexing services, Qdrant synchronization, Formly upload UI, document feature API wiring, and Playwright acceptance coverage.

## Post-Design Constitution Check

- [x] Design still preserves REST-only frontend/backend interaction.
- [x] Service layer remains the owner of upload orchestration, ingestion transitions, audit emission, and indexing failure handling.
- [x] Contracts cover the documents API surface before implementation.
- [x] Test-first work can start with failing backend API tests, service tests, Angular component tests, and Playwright document-upload flows.
- [x] JWT, RBAC, tenant isolation, and Italian localization remain explicit in the design.
- [x] Reusing the existing Formly file field and `AttachmentIndexerService` concepts keeps the solution simpler than introducing brand-new frontend and backend abstractions.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| None | N/A | N/A |
