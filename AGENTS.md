# AssistDoc Development Guidelines

Auto-generated from all feature plans. Last updated: 2026-05-11

## Active Technologies
- PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), YAML for API contracts + Laravel API stack, Angular SPA, JWT authentication, Bootstrap, ngx-formly (002-user-auth)
- Relational database for users, tenants, role assignments, and audit events (002-user-auth)
- PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), YAML for API contracts + Laravel API stack, Angular SPA, Bootstrap Italia styling on top of Bootstrap, ngx-formly, JWT authentication, Laravel queue workers, Qdrant vector search, existing `EmbeddingService`, existing `AttachmentIndexerService` chunking pattern (003-document-ingestion)
- Relational database for `documents` and `document_segments`, private file storage for uploaded source files, Qdrant for semantic retrieval vectors (003-document-ingestion)

- PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), SQL for relational schema, YAML for infrastructure and API contracts + Laravel API stack, Angular SPA, Bootstrap, ngx-formly, ngx-datatable, Laravel queue workers, JWT authentication, Qdrant vector search, Ollama chat and embedding models (001-private-doc-assistant)

## Project Structure

```text
backend/
frontend/
tests/
```

## Commands

npm test && npm run lint

## Code Style

PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), SQL for relational schema, YAML for infrastructure and API contracts: Follow standard conventions

## Recent Changes
- 003-document-ingestion: Added PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), YAML for API contracts + Laravel API stack, Angular SPA, Bootstrap Italia styling on top of Bootstrap, ngx-formly, JWT authentication, Laravel queue workers, Qdrant vector search, existing `EmbeddingService`, existing `AttachmentIndexerService` chunking pattern
- 002-user-auth: Added PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), YAML for API contracts + Laravel API stack, Angular SPA, JWT authentication, Bootstrap, ngx-formly

- 001-private-doc-assistant: Added PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), SQL for relational schema, YAML for infrastructure and API contracts + Laravel API stack, Angular SPA, Bootstrap, ngx-formly, ngx-datatable, Laravel queue workers, JWT authentication, Qdrant vector search, Ollama chat and embedding models

<!-- MANUAL ADDITIONS START -->
<!-- MANUAL ADDITIONS END -->
