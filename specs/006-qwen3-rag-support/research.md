# Research: Configured Qwen RAG Support

## Decision 1: Load the retrieval profile from configuration

- **Decision**: Represent the active retrieval configuration in a code file, with `Qwen` as the default profile, and resolve embedding model, generation model, tokenizer identifier, token window, embedding dimensions, and availability from that configuration at runtime.
- **Rationale**: The feature no longer needs operator-managed retrieval-profile CRUD. Configuration gives one source of truth for indexing, retrieval, validation, and chat generation while keeping deployment changes explicit.
- **Alternatives considered**:
  - Reuse environment variables only per deployment.
    - Rejected because it cannot preserve structured profile metadata or historical traceability.
  - Keep retrieval profiles in database tables and expose CRUD APIs.
    - Rejected because the user explicitly wants retrieval-profile management removed from runtime flows.

## Decision 2: Enforce chunk sizing with tokenizer-aware measurement, not character counts

- **Decision**: Replace the current fixed-character `chunkTextWithOverlap` behavior with a profile-aware tokenizer service that measures both chunk size and overlap in tokens before segments are persisted or indexed.
- **Rationale**: The requested feature explicitly requires token-based chunk-size profiles, token-based overlaps, and tokenizer limit checks. Character counts are not stable proxies for token windows and would allow non-compliant chunks to enter storage.
- **Alternatives considered**:
  - Keep character-based chunking and lower size values conservatively.
    - Rejected because it still cannot guarantee token-limit compliance across models.
  - Truncate oversized chunks at embedding time only.
    - Rejected because it would silently alter stored evidence and break retrieval traceability.

## Decision 3: Keep preparation orchestration in `DocumentProcessingService`

- **Decision**: Extend `DocumentProcessingService` to create and own explicit preparation runs, validation failures, and state transitions for profile-aware indexing.
- **Rationale**: The constitution requires workflow transitions and business rules to live in Services. The existing processing flow already coordinates extraction, segmentation, indexing, and failure handling, so it is the correct orchestration boundary for profile-aware preparation.
- **Alternatives considered**:
  - Move token validation into controllers.
    - Rejected because controllers must remain thin and should not own workflow state.
  - Push compatibility logic into repositories.
    - Rejected because repositories should persist and query data, not decide domain rules.

## Decision 4: Persist profile identity with segments and vectors

- **Decision**: Add profile identity and preparation-run identity to relational segment metadata and Qdrant payloads.
- **Rationale**: Retrieval must never mix embeddings with incompatible dimensions or tokenization assumptions. Compatibility must therefore be enforceable both in the relational source of truth and in vector-search filters.
- **Alternatives considered**:
  - Keep profile data only at the document level.
    - Rejected because reprocessing requires precise traceability for which active segment set and vector set belong to which profile.
  - Use one global Qdrant collection without profile metadata.
    - Rejected because dimension and profile compatibility cannot be enforced safely.

## Decision 5: Preserve `AiSearchService::askLlamaWithContext` as the answer contract

- **Decision**: Treat the prompt structure already defined in `backend/app/Services/AI/AiSearchService.php` `askLlamaWithContext` as the canonical user-answer template for grounded responses, regardless of whether the generation model is `llama3.2` or the configured `Qwen` profile.
- **Rationale**: The user explicitly requested this behavior. The existing prompt already encodes key product rules: answer only from provided context, reply in Italian, avoid hallucinations, and emit the fallback phrase when the answer is not present.
- **Alternatives considered**:
  - Create a second Qwen-specific prompt.
    - Rejected because it would drift answer semantics between models and weaken acceptance criteria.
  - Route all answers through the simpler `ChatCompletionService` lead-evidence formatter.
    - Rejected because that path does not preserve the fuller grounded-prompt behavior already present in `askLlamaWithContext`.

## Decision 6: Reprocess existing documents instead of backfilling in place

- **Decision**: Require an explicit reprocessing run whenever a document must become compatible with a different retrieval model profile or chunking profile.
- **Rationale**: Reprocessing preserves auditability, lets the system validate token limits against the selected tokenizer, and ensures outdated vectors are withdrawn before replacement data becomes active.
- **Alternatives considered**:
  - Convert segment metadata in place without regenerating vectors.
    - Rejected because embeddings and chunk boundaries would no longer match the stored profile.
  - Auto-reprocess all ready documents when the configured retrieval profile changes.
    - Rejected because it increases rollout risk and removes operator control.

## Decision 7: Keep rollback simple through configuration updates

- **Decision**: Roll back a problematic retrieval profile by changing the configuration so the deployment no longer uses it, while retaining historical visibility for already indexed documents. Keep chunking profiles deployment-configured and seed the default `small`, `medium`, and `large` set from code rather than exposing lifecycle CRUD.
- **Rationale**: This respects audit requirements and avoids destructive cleanup during incident response. It also matches the updated product requirement that both retrieval and chunking behavior are configuration-driven, not operator-managed at runtime.
- **Alternatives considered**:
  - Delete the profile and its history immediately.
    - Rejected because it would break traceability.
  - Shut down all retrieval while profile issues are investigated.
    - Rejected because the simpler and safer rollback is to isolate only the affected configuration.
