# Data Model: Configured Qwen RAG Support

## Overview

This feature extends the current document and segment model with explicit profile metadata and preparation-run traceability so ingestion, retrieval, and chat answer generation remain compatible across multiple model configurations. Document upload automatically uses the configured `small`, `medium`, and `large` chunking runs rather than exposing those selectors in the upload path.

## Entities

### RetrievalModelProfile

- **Purpose**: Defines one supported semantic-processing profile loaded from code configuration and used by ingestion, retrieval, and optionally grounded answer generation.
- **Fields**:
  - `name`
  - `slug`
  - `generation_model`
  - `embedding_model`
  - `tokenizer_key`
  - `token_window`
  - `embedding_dimensions`
  - `available_for_new_runs`
  - `is_default`
  - `created_at`
  - `updated_at`
- **Validation**:
  - `name` and `slug` are unique in configuration.
  - `token_window` must be greater than zero.
  - `embedding_dimensions` must be greater than zero.
  - `available_for_new_runs` is boolean.
- **Relationships**:
  - One-to-many with `ChunkPreparationRun` through stored traceability only
  - One-to-many with `SearchableChunk` through stored traceability only

### ChunkingProfile

- **Purpose**: Reusable token-based chunk definition synced from code configuration and selected for a preparation run.
- **Fields**:
  - `id`
  - `name`
  - `slug`
  - `chunk_size_tokens`
  - `overlap_tokens`
  - `active`
  - `notes`
  - `created_at`
  - `updated_at`
- **Validation**:
  - `name` and `slug` are unique.
  - `chunk_size_tokens` must be positive.
  - `overlap_tokens` must be zero or positive.
  - `overlap_tokens` must be less than `chunk_size_tokens`.
  - The default configuration set must include `small`, `medium`, and `large`.
- **Relationships**:
  - One-to-many with `ChunkPreparationRun`
  - One-to-many with `SearchableChunk`

### ChunkPreparationRun

- **Purpose**: Tracks one document-processing attempt under exactly one retrieval model profile and one chunking profile.
- **Fields**:
  - `id`
  - `tenant_id`
  - `document_id`
  - `retrieval_model_profile_id`
  - `chunking_profile_id`
  - `status`
  - `requested_by_user_id`
  - `failure_code`
  - `failure_message`
  - `started_at`
  - `completed_at`
  - `created_at`
  - `updated_at`
- **Validation**:
  - `status` must be one of `queued`, `processing`, `ready`, `failed`.
  - Selected profile and chunking profile must both be available when the run is created.
  - Reprocessing for a document is rejected when another active run is already `queued` or `processing`.
- **Relationships**:
  - Belongs to `Document`
  - Belongs to `RetrievalModelProfile`
  - Belongs to `ChunkingProfile`
  - One-to-many with `SearchableChunk`
  - One-to-many with `PreparationValidationFailure`
- **State transitions**:
  - `queued` -> `processing`
  - `processing` -> `ready`
  - `processing` -> `failed`
  - `failed` -> `queued`

### SearchableChunk

- **Purpose**: Represents one persisted chunk eligible for semantic retrieval after passing tokenizer-limit validation.
- **Fields**:
  - `id`
  - `tenant_id`
  - `document_id`
  - `chunk_preparation_run_id`
  - `retrieval_model_profile_id`
  - `chunking_profile_id`
  - `segment_index`
  - `content_text`
  - `source_label`
  - `token_count`
  - `searchable`
  - `activated_at`
  - `retired_at`
- **Validation**:
  - `token_count` must be greater than zero.
  - `token_count` must be less than or equal to the selected profile `token_window`.
  - `segment_index` must be unique per preparation run.
- **Relationships**:
  - Belongs to `Document`
  - Belongs to `ChunkPreparationRun`
  - Belongs to `RetrievalModelProfile`
  - Belongs to `ChunkingProfile`
- **Notes**:
  - Existing `DocumentSegment` records can be evolved into this enriched shape instead of introducing a parallel table if that keeps migration cost lower.
  - Deployment-time configuration updates may add or retire profiles, but the public API remains read-only for chunking-profile discovery.

### PreparationValidationFailure

- **Purpose**: Stores token-limit or profile-compatibility failures produced during chunk preparation.
- **Fields**:
  - `id`
  - `chunk_preparation_run_id`
  - `document_id`
  - `failure_code`
  - `failure_message`
  - `segment_index`
  - `measured_token_count`
  - `allowed_token_count`
  - `created_at`
- **Validation**:
  - `failure_code` is required.
  - Token counts are required for tokenizer-limit failures.
- **Relationships**:
  - Belongs to `ChunkPreparationRun`
  - Belongs to `Document`

## Existing Entity Extensions

### Document

- Add `active_retrieval_model_profile_id`
- Add `active_chunking_profile_id`
- Add `active_preparation_run_id`
- Continue to use existing document workflow states for upload readiness and failure display

### DocumentSegment or evolved SearchableChunk payload

- Add profile and preparation-run foreign keys
- Add `token_count`
- Add lifecycle timestamps for activation and retirement

## Configuration-Synced Defaults

- `small`: compact chunking profile for short documents and dense retrieval
- `medium`: balanced default chunking profile for general documents
- `large`: wider chunking profile for longer sections and default-profile-aligned runs
- Upload workflows consume these defaults automatically and keep the document-load UI free of chunking-profile selectors.
- `Qwen`: default retrieval profile loaded from configuration for all document preparation unless changed at deployment time.

## Qdrant Payload Shape

- `tenant_id`
- `document_id`
- `segment_id`
- `segment_index`
- `chunk_preparation_run_id`
- `retrieval_model_profile`
- `retrieval_model_profile_id`
- `chunking_profile_id`
- `embedding_model`
- `embedding_dimensions`
- `token_count`
- `document_status`
- `filename`
- `source_label`
- `content_text`

## Retrieval Compatibility Rules

- A semantic query may search only chunks whose stored retrieval profile matches the configured active query profile.
- Embedding generation for a query must use the embedding model declared by the configured active retrieval profile.
- Grounded answer generation may use the same profile's generation model, but the final answer prompt must preserve the `askLlamaWithContext` instruction set.
- When a document is reprocessed, prior active chunks are retired before newly indexed chunks become searchable.

## Audit Requirements

- Profile create, update, activate, deactivate
- Chunking profile create, update, activate, deactivate
- Preparation run requested, started, failed, completed
- Token-limit violation recorded
- Document reprocessed under a new profile combination
