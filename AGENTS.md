# AssistDoc Development Guidelines

Last updated: 2026-05-25

## Stack
- Backend: PHP 8.3, Laravel 12
- Frontend: TypeScript 5.x, Angular 20, Bootstrap, ngx-formly, ngx-datatable
- Auth: JWT
- Search and RAG: Qdrant for vectors, Ollama for embeddings and generation
- Storage: relational database for users, tenants, documents, chat, and RAG metadata; private file storage for uploads

## Project Layout
```text
backend/
frontend/
tests/
```

## Configuration Boundaries

Keep the config split simple:

- `backend/config/services.php`
  - Generic third-party and infrastructure endpoints
  - Qdrant URL and collection
  - Ollama base URLs
  - shared auth/token secret

- `backend/config/rag.php`
  - Default retrieval profile
  - Retrieval model names
  - Tokenizer key and token window
  - Embedding dimensions
  - Whether the profile can be used for new runs

Preferred env vars:

- `QDRANT_URL`
- `QDRANT_COLLECTION`
- `OLLAMA_URL`
- `OLLAMA_GENERATION_MODEL`
- `OLLAMA_EMBEDDING_MODEL`
- `OLLAMA_EMBEDDING_DIMENSIONS`
- `OLLAMA_EMBEDDING_MAX_INPUT_CHARS`
- `TOKEN_SECRET`

Rules of thumb:

- Use `services.php` for endpoints and transport details.
- Use `rag.php` for profile defaults and model selection.
- Let `App\Services\Rag\RetrievalModelProfileService` resolve the active profile from `config('rag.default_retrieval_profile.slug')`.
- Let `App\Services\AI\EmbeddingService` and `App\Services\AI\AiSearchService` use the selected profile first, then the RAG defaults.

## Commands
- `npm test && npm run lint`

## Code Style
- Follow standard Laravel and Angular conventions.
- Keep naming consistent between config keys, env vars, services, and tests.
- Avoid duplicate aliases unless they are needed for backward compatibility.

## Active Technologies
- TypeScript 5.x, Angular 18 in the current frontend package + Bootstrap 5, Bootstrap Icons, `@ngx-formly/bootstrap`, ng-bootstrap for overlay/navigation primitives, existing Angular router and standalone components (011-responsive-layout)
- N/A for this feature; layout state is local to the UI (011-responsive-layout)
- PHP 8.3 (Laravel 12), TypeScript 5.x (Angular SPA) + Laravel API middleware and controllers, Angular reactive forms, Bootstrap, ng-bootstrap, existing `EnterpriseUserLifecycleService`, existing chat/document services (012-admin-tenant-user-edit)
- Existing PostgreSQL-style relational storage for users, tenants, role assignments, access methods, and audit events; no new tables expected (012-admin-tenant-user-edit)

## Recent Changes
- 011-responsive-layout: Added TypeScript 5.x, Angular 18 in the current frontend package + Bootstrap 5, Bootstrap Icons, `@ngx-formly/bootstrap`, ng-bootstrap for overlay/navigation primitives, existing Angular router and standalone components
