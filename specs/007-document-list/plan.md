# Implementation Plan: Document List Management

**Branch**: `007-document-list` | **Date**: 2026-05-21 | **Spec**: [spec.md](/home/enricooliva/Workspace/AssistDoc/specs/007-document-list/spec.md)
**Input**: Feature specification from `/specs/007-document-list/spec.md`

## Summary

Add a tenant-scoped document browsing flow that returns paginated documents with tags and uploader metadata, and add a safe single-document soft delete flow that removes the record from the normal list while preserving relational traceability and keeping Qdrant segment data coherent by deleting the document's vectors and retiring its stored segments.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12), TypeScript 5.x (Angular SPA; current frontend package is Angular 18)  
**Primary Dependencies**: Laravel API stack, Angular SPA, JWT auth, Bootstrap, ngx-datatable, existing `DocumentService`, `DocumentRepository`, `DocumentSegmentRepository`, `QdrantService`  
**Storage**: PostgreSQL-style relational storage for documents, document_segments, users, tenants, and audit events; Qdrant collections for semantic vectors  
**Testing**: PHPUnit feature/unit tests, Angular component tests, Playwright E2E tests  
**Target Platform**: Browser-based web application with Laravel API backend  
**Project Type**: web application  
**Performance Goals**: First page of the document list should load within 2 seconds for normal tenant sizes; delete confirmation should complete within 1 second from the UI once the action is triggered  
**Constraints**: Tenant isolation is mandatory; all UI text must remain Italian; deletion must be soft delete only; Qdrant vectors must stay aligned with relational document state; API contracts are versioned and REST-based  
**Scale/Scope**: Tenant document inventory browsing and single-document deletion only; no restore, no permanent purge, no document editing in this feature

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
specs/007-document-list/
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
│   ├── Http/Controllers/Api/V1/DocumentController.php
│   ├── Repositories/DocumentRepository.php
│   ├── Repositories/DocumentSegmentRepository.php
│   └── Services/Documents/
└── tests/
    ├── Feature/Documents/
    └── Unit/Documents/

frontend/
├── src/app/features/documents/
└── tests/e2e/
```

**Structure Decision**: Extend the existing backend document API and document service layer, and evolve the existing Angular `documents` feature area (`DocumentsPageComponent`, `DocumentListComponent`, `DocumentApiService`) rather than introducing a separate module.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| Queue-based Qdrant cleanup after soft delete | Qdrant is external to the relational transaction boundary, so vector deletion must be coordinated separately to keep semantic chunks consistent with the deleted document state | Purely synchronous vector deletion was rejected because a transient Qdrant failure would make delete operations brittle and could leave the user-facing document state out of sync with the vector store |

