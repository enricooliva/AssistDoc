# Implementation Plan: AssistDoc Private Document Assistant

**Branch**: `001-private-doc-assistant` | **Date**: 2026-05-08 | **Spec**: [spec.md](/mnt/c/Workspace/spec-kit/AssistDoc/specs/001-private-doc-assistant/spec.md)
**Input**: Feature specification from `/specs/001-private-doc-assistant/spec.md` plus planning note: "simple angular interface style chatGPT and Authentication + tenant isolation"

## Summary

Deliver the AssistDoc MVP as a containerized web application with an Angular SPA and Laravel API that supports tenant-scoped authentication, document upload and asynchronous indexing, semantic search, cited chat responses, and audit logging. The frontend will use a simple ChatGPT-style layout for the chat experience while preserving the constitution's document-centric, Italian-localized UX requirements and routing all data access through authenticated, role-protected REST APIs.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), SQL for relational schema, YAML for infrastructure and API contracts  
**Primary Dependencies**: Laravel API stack, Angular SPA, Bootstrap, ngx-formly, ngx-datatable, Laravel queue workers, JWT authentication, Qdrant vector search, Ollama chat model plus `mxbai-embed-large` embeddings (1024 dimensions) served from `http://192.168.5.137:11434/api/embed`  
**Storage**: MySQL for relational data, Qdrant for tenant-scoped 1024-dimensional vector embeddings, object/file storage for uploaded documents  
**Testing**: PHPUnit/Pest for backend API and workflow tests, Angular unit/component tests, Playwright end-to-end tests, contract validation for OpenAPI  
**Target Platform**: Linux containers orchestrated with Docker Compose for local and small-scale deployment  
**Project Type**: Containerized web application with separate frontend and backend applications inside one modular-monolith repository  
**Performance Goals**: 95% of cited chat responses or explicit insufficient-information responses within 10 seconds; 95% of successful uploads searchable within 10 minutes; first-question flow completed by 90% of users in under 3 minutes  
**Constraints**: Stateless JWT auth; SSO required outside local development; strict server-side tenant isolation on every resource; Italian UI text; backend business logic only in Services; asynchronous indexing; no frontend bypass of APIs  
**Scale/Scope**: MVP for multiple company tenants, three base roles, low-thousands of documents per tenant, concurrent tenant usage across document upload, search, chat, and audit workflows

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
specs/001-private-doc-assistant/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── assistdoc-openapi.yaml
└── tasks.md
```

### Source Code (repository root)

```text
backend/
├── app/
│   ├── Http/Controllers/Api/V1/
│   ├── Models/
│   ├── Repositories/
│   └── Services/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── tests/
    ├── Feature/Api/
    ├── Feature/Auth/
    ├── Feature/Workflow/
    └── Unit/Services/

frontend/
├── src/
│   ├── app/
│   │   ├── core/
│   │   │   ├── auth/
│   │   │   ├── http/
│   │   │   ├── layout/
│   │   │   └── tenant/
│   │   ├── features/
│   │   │   ├── audit/
│   │   │   ├── auth/
│   │   │   ├── chat/
│   │   │   ├── documents/
│   │   │   └── search/
│   │   └── shared/
│   └── assets/i18n/
└── tests/
    ├── component/
    └── e2e/

infra/
└── docker/
    ├── backend/
    ├── frontend/
    └── compose/
```

**Structure Decision**: Use a web-application split with `backend/`, `frontend/`, and `infra/` directories because the constitution already fixes an Angular SPA plus REST backend boundary. The backend remains a modular monolith through domain-oriented Services and Repositories inside one Laravel app.

## Phase 0: Research Summary

- Finalize authentication approach: JWT bearer tokens for all environments, with local username/password only in development and external SSO integration point kept behind the same auth contract.
- Finalize tenant isolation strategy: resolve tenant context from authenticated identity server-side, apply tenant scoping in controllers, services, repositories, jobs, and vector queries, and audit all authorization failures.
- Finalize chat UI approach: a simple two-pane Angular layout with left-side conversation/history navigation and a main answer composer area modeled after ChatGPT, but styled with restrained Bootstrap document-app patterns.
- Finalize asynchronous ingestion workflow: upload creates metadata record immediately, queue worker extracts content, segments text, writes embeddings, then marks document indexed or failed.

## Phase 1: Design Outputs

- `research.md` captures the implementation decisions and rejected alternatives for authentication, tenant isolation, chat UX, ingestion pipeline, audit logging, and contract structure.
- `data-model.md` defines entities, fields, validation rules, and workflow transitions for tenants, users, role assignments, documents, segments, conversations, messages, search queries, and audit events.
- `contracts/assistdoc-openapi.yaml` defines versioned REST endpoints for auth, documents, search, chat, and audit logging with schemas, role rules, and standard error payloads.
- `quickstart.md` describes local setup with Docker Compose, required services, seed data expectations, test execution, and manual verification of the critical MVP flows.
- Agent context will be refreshed from this plan after the design files are written.

## Phase 2 Preview

- Derive dependency-ordered tasks that start with contract tests, persistence schema, auth and tenant middleware, document ingestion workflow, vector retrieval, Angular auth shell, document management UI, ChatGPT-style chat UI, audit UI, and Playwright acceptance coverage.

## Post-Design Constitution Check

- [x] Design still preserves REST-only frontend/backend interaction.
- [x] Service layer remains the owner of auth orchestration, tenant enforcement, workflow transitions, and transaction boundaries.
- [x] Contracts cover every planned API surface before implementation.
- [x] Test-first work can start with failing backend API tests, Angular component tests, and Playwright flows.
- [x] JWT, RBAC, SSO boundary, and Italian localization remain explicit in the design.
- [x] Added UI simplicity does not conflict with the constitution's institutional, document-centric baseline.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| None | N/A | N/A |
