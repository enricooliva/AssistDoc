# Implementation Plan: Text Content Ingestion

**Branch**: `013-text-content-ingestion` | **Date**: 2026-06-09 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/013-text-content-ingestion/spec.md`

## Summary

Extend the existing document ingestion flow so authorized users can add knowledge either by uploading a file or by pasting text directly in the documents area. The implementation keeps one canonical `Document` lifecycle, stores both source types in private storage, reuses the current extraction, chunking, and embedding pipeline, and updates the Angular document form with Bootstrap plus `ngx-formly` controls instead of introducing a separate content module.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20 SPA)  
**Primary Dependencies**: Laravel controllers/requests/services/repositories, Angular reactive forms, Bootstrap 5, `@ngx-formly/core`, `@ngx-formly/bootstrap`, existing Qdrant and Ollama integrations  
**Storage**: Existing relational database for document metadata and preparation runs, plus private file storage for uploaded files and normalized pasted text  
**Testing**: PHPUnit feature and unit tests, Angular unit tests, Playwright E2E for the document workflow  
**Target Platform**: Web application  
**Project Type**: Full-stack web application  
**Performance Goals**: Keep document creation and list refresh within the current UX envelope; pasted text should enter the same asynchronous preparation flow already used for file uploads  
**Constraints**: Preserve JWT auth, tenant isolation, current document status lifecycle, private storage, existing Qdrant collection strategy by embedding dimensions, Italian UI copy, and Bootstrap/Formly conventions  
**Scale/Scope**: One new ingestion mode inside the current documents feature, with one additional API endpoint, one schema extension on `documents`, and no new bounded context

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- Specification exists and contains no unresolved `[NEEDS CLARIFICATION]`.
- Planned scope traces to documented requirements and user stories.
- Architecture preserves REST API-first boundaries and does not bypass APIs.
- Backend design follows `Controller -> Service -> Repository -> Model`.
- Business rules, workflow transitions, and transaction boundaries live in
  Services.
- Contracts are identified for every API, including schemas, validation, auth,
  and error formats.
- Test-first coverage is planned before implementation starts:
  API success/failure, authorization, workflow transition, component, and
  critical Playwright E2E tests as applicable.
- Security design enforces stateless JWT auth, SSO outside local, and RBAC with
  endpoint-level role declarations.
- Frontend plan preserves Angular SPA module boundaries and Italian (`it-IT`)
  localization requirements.
- Any added complexity is explicitly justified with a simpler alternative and a
  rollback strategy.

## Project Structure

### Documentation (this feature)

```text
specs/013-text-content-ingestion/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── text-content-ingestion-openapi.yaml
└── tasks.md
```

### Source Code (repository root)

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/DocumentController.php
│   │   └── Requests/Document*.php
│   ├── Models/Document.php
│   ├── Repositories/DocumentRepository.php
│   └── Services/Documents/
│       ├── DocumentService.php
│       ├── DocumentProcessingService.php
│       └── DocumentIndexerService.php
├── database/migrations/
└── tests/
    ├── Feature/Documents/
    └── Unit/Documents/

frontend/
├── src/
│   └── app/
│       ├── features/documents/
│       │   ├── document-api.service.ts
│       │   ├── document-list.component.ts
│       │   ├── document-upload.component.ts
│       │   └── document.models.ts
│       └── shared/formly/
└── tests/
    └── e2e/
```

**Structure Decision**: This remains a Laravel API plus Angular SPA feature enhancement. The backend work stays inside the existing document controller/request/service/repository/model layers, while the frontend work stays inside the current documents feature and continues to use Bootstrap and `ngx-formly`. No new module, alternate upload shell, or second indexing pipeline is introduced.

## Complexity Tracking

No constitution violations require justification. The feature is delivered by extending the current document flow rather than introducing a parallel content-ingestion subsystem.
