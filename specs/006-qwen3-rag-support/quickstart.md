# Quickstart: Configured Qwen RAG Support

## Goal

Verify that AssistDoc can prepare documents with the configured default `Qwen` retrieval profile, automatically start the three chunking runs on upload, enforce tokenizer-aware chunk limits, and still generate grounded user answers with the `AiSearchService::askLlamaWithContext` prompt contract.

## Prerequisites

- Laravel backend and Angular frontend dependencies installed
- Ollama models available for:
  - the configured `Qwen` generation profile
  - the configured embedding model that returns 4,096 values for the default retrieval profile
- Qdrant running and reachable by the backend
- A tenant user with document-management permissions
- At least one sample document with enough text to generate multiple chunks

## Setup

1. Configure the default retrieval model profile:
   - `Qwen` profile with 40,000-token window and 4,096 embedding dimensions
2. Configure at least three chunking profiles through code or seeding:
   - `small`: compact token chunk size for short documents
   - `medium`: balanced default token chunk size and overlap
   - `large`: wider token chunk size with valid overlap for longer documents and default-profile-aligned runs
3. Ensure the active chat answer path points to the same grounded prompt rules used by `AiSearchService::askLlamaWithContext`.

## Verification Flow

1. Upload a document through the existing document workflow.
2. Confirm the upload automatically starts embedding runs for the `small`, `medium`, and `large` chunking profiles.
3. Confirm the final preparation state reaches `ready` and the active chunking profile is `large`.
4. Inspect the stored chunk records and verify:
   - every chunk stores the selected retrieval model profile and chunking profile
   - every chunk stores a `token_count`
   - no chunk exceeds 40,000 tokens
5. Inspect Qdrant payload metadata and verify:
   - `retrieval_model_profile` or equivalent profile identifier is present
   - `embedding_dimensions` is `4096`
   - retired vectors from prior runs are absent from the active retrieval path
6. Submit a grounded chat question whose answer is present in the indexed document.
7. Confirm the user-visible answer:
   - is in Italian
   - is based only on the retrieved context
   - follows the fallback rule from `askLlamaWithContext` when the answer is not present
8. Submit a question with no support in the indexed document and confirm the response falls back to `Non è specificato nei documenti.`

## Failure Path Checks

1. Change the code configuration so one chunking profile is invalid for the chosen model profile.
2. Upload a document and confirm the automatic embedding flow fails before any non-compliant chunks become searchable.
3. Verify the preparation run records a validation failure with the measured and allowed token counts.
4. Break the retrieval profile configuration and confirm:
   - new preparation runs fail fast with a clear configuration error
   - existing historically indexed documents still show which profile created them

## Test-First Expectations

- Backend feature tests should fail first for:
  - configured retrieval profile loading
  - chunking profile validation
  - preparation run creation and failure handling
  - retrieval compatibility filtering
  - grounded chat response behavior under the configured retrieval profile
- Unit tests should fail first for:
  - tokenizer-aware chunk builder behavior
  - token-limit validation
  - configured profile resolution inside embedding and retrieval services
  - prompt selection that preserves `askLlamaWithContext`
