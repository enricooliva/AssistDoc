# Quickstart: Secure Document Ingestion

## Goal

Verify that document upload uses the shared Formly file component, enforces Italian-ready validation, stores the uploaded file, extracts readable text from uploaded PDFs, and completes chunk and embedding indexing through the new backend document pipeline.

## Prerequisites

- Backend dependencies installed and application configured.
- Frontend dependencies installed and Angular app configured.
- Qdrant available for vector writes.
- Ollama embeddings reachable at `http://192.168.5.137:11434/api/embeddings` with model `mxbai-embed-large`.
- Auth seed data available with at least one `operator` user and one `viewer` user.

## Local Run

1. Start the backend application from `backend/`.
2. Start the frontend application from `frontend/`.
3. Ensure Qdrant is reachable from the backend environment.
4. Ensure background job execution is active for document processing.
5. Confirm the embedding endpoint returns 1024-dimensional vectors for `mxbai-embed-large`.
6. Confirm overlong chunks are truncated before embedding so the model does not reject them for context-length overflow.

## Manual Verification Flow

### Upload Success

1. Sign in as an `operator`.
2. Open the documents page.
3. Confirm the upload card renders a Formly-based file field with Bootstrap Italia-consistent label, help text, validation state, and submit feedback.
4. Try submitting without a file and confirm the validation message is shown in Italian.
5. Select a supported document and submit it.
6. Prefer a PDF fixture that contains selectable text, submit it, and confirm the API returns `201`.
7. Confirm the new document appears in the list with `accepted` or `queued`, then advances through `processing` to `ready`.

### Authorization Guard

1. Sign in as a `viewer`.
2. Open the documents page.
3. Confirm the upload action is hidden or disabled in the UI.
4. Attempt a direct API upload to `POST /api/v1/documents`.
5. Confirm the API returns `403`.

### Failure and Retry

1. Upload an unreadable or intentionally failing document fixture.
2. Confirm the document ends in `failed` with a user-readable reason.
3. Trigger retry as an `operator`.
4. Confirm the document returns to `queued` and a new processing attempt begins.

### Storage and Indexing

1. After a successful upload, confirm the document has a private `storage_path` in the backend persistence layer.
2. Confirm `document_segments` rows were created for the uploaded document.
3. For a PDF upload, confirm at least one segment contains extracted PDF text rather than raw binary content.
4. Confirm each retained segment has a corresponding vector record in Qdrant.
5. Confirm the document is marked `ready` only after segment persistence and vector writes succeed.

### Tenant Isolation

1. Upload a document in tenant A as an `operator`.
2. Sign in as a user from tenant B.
3. Confirm tenant B cannot list, view, retry, or retrieve any detail for tenant A's document.

## Required Automated Tests Before Implementation Completion

- Backend feature tests for upload success, upload validation failure, retry success, retry conflict, authorization rejection, and tenant isolation.
- Backend unit tests for `DocumentIndexerService` chunking, cleanup, payload generation, and empty-content handling.
- Backend unit tests for `PdfTextExtractorService` literal PDF content extraction.
- Backend unit or feature tests for `DocumentProcessingService` state transitions and failure handling.
- Angular component tests for the document upload Formly configuration and Italian validation feedback.
- Playwright E2E tests for successful operator upload and blocked viewer upload.
