# AssistDoc Development Guidelines

Auto-generated from all feature plans. Last updated: 2026-05-21

## Active Technologies
- PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), YAML for API contracts + Laravel API stack, Angular SPA, JWT authentication, Bootstrap, ngx-formly (002-user-auth)
- Relational database for users, tenants, role assignments, and audit events (002-user-auth)
- PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), YAML for API contracts + Laravel API stack, Angular SPA, Bootstrap Italia styling on top of Bootstrap, ngx-formly, JWT authentication, Laravel queue workers, Qdrant vector search, existing `EmbeddingService`, existing `AttachmentIndexerService` chunking pattern (003-document-ingestion)
- Relational database for `documents` and `document_segments`, private file storage for uploaded source files, Qdrant for semantic retrieval vectors (003-document-ingestion)
- PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20) + Laravel API stack, Angular SPA, Bootstrap, ngx-formly, JWT authentication, Qdrant vector search, existing `SemanticSearchService`, existing `ChatCompletionService`, Ollama/Llama3.2 response generation (004-chat-semantic-answer)
- Relational database for `chat_conversations`, `chat_messages`, and citations; Qdrant for tenant-scoped retrieval vectors; existing private document storage (004-chat-semantic-answer)
- PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), YAML for API contracts + Laravel API stack, Angular SPA, Bootstrap, ngx-formly, JWT authentication, Laravel queue workers, Qdrant vector search, existing `DocumentProcessingService`, `DocumentIndexerService`, `EmbeddingService`, `SemanticSearchService`, and `AiSearchService`; Ollama-hosted `llama3.2` and `Qwen3` generation models; profile-aware tokenizer counting service introduced in the backend (006-qwen3-rag-support)
- Relational database for documents, segments, and new profile/run metadata; Qdrant collections for profile-compatible semantic vectors; existing private document storage (006-qwen3-rag-support)
- PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20) + Laravel API stack, Angular SPA, Bootstrap, ngx-formly, ngx-datatable, JWT authentication, Qdrant vector search, Ollama-hosted generation and embedding models (006-qwen3-rag-support)
- PostgreSQL-style relational storage for profiles, documents, runs, and audit events; private file storage for uploaded source files; Qdrant for vector payloads (006-qwen3-rag-support)
- PHP 8.3 (Laravel 12), TypeScript 5.x (Angular SPA; current frontend package is Angular 18) + Laravel API stack, Angular SPA, JWT auth, Bootstrap, ngx-datatable, existing `DocumentService`, `DocumentRepository`, `DocumentSegmentRepository`, `QdrantService` (007-document-list)
- PostgreSQL-style relational storage for documents, document_segments, users, tenants, and audit events; Qdrant collections for semantic vectors (007-document-list)

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
- 007-document-list: Added PHP 8.3 (Laravel 12), TypeScript 5.x (Angular SPA; current frontend package is Angular 18) + Laravel API stack, Angular SPA, JWT auth, Bootstrap, ngx-datatable, existing `DocumentService`, `DocumentRepository`, `DocumentSegmentRepository`, `QdrantService`
- 006-qwen3-rag-support: Added PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20) + Laravel API stack, Angular SPA, Bootstrap, ngx-formly, ngx-datatable, JWT authentication, Qdrant vector search, Ollama-hosted generation and embedding models
- 006-qwen3-rag-support: Added PHP 8.3 (Laravel 12), TypeScript 5.x (Angular 20), YAML for API contracts + Laravel API stack, Angular SPA, Bootstrap, ngx-formly, JWT authentication, Laravel queue workers, Qdrant vector search, existing `DocumentProcessingService`, `DocumentIndexerService`, `EmbeddingService`, `SemanticSearchService`, and `AiSearchService`; Ollama-hosted `llama3.2` and `Qwen3` generation models; profile-aware tokenizer counting service introduced in the backend


<!-- MANUAL ADDITIONS START -->
<!-- MANUAL ADDITIONS END -->
