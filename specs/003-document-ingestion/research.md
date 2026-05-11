# Research: Secure Document Ingestion

## Formly File Input Reuse

- Decision: Reuse `frontend/src/app/shared/dynamic-form/input-file/input-file.component.ts` as the base custom Formly type for upload selection and extend it for Bootstrap Italia-compatible rendering, validation presentation, and real `File` object handling.
- Rationale: The component already centralizes file selection behavior and fits the constitution's requirement to use `ngx-formly` for forms. Reusing it avoids parallel upload widgets and keeps file-field behavior consistent across the app.
- Alternatives considered:
  - Create a brand-new document-only Formly type: rejected because it duplicates selection behavior already present in the shared layer.
  - Keep the current template-driven upload form: rejected because it bypasses the shared form architecture and cannot meet the Formly requirement from the constitution.

## Bootstrap Italia Upload UX

- Decision: Keep the upload page in the `features/documents` slice but refactor it to render a Formly form whose file field uses Bootstrap Italia-compatible classes, help text, error styling, and loading feedback.
- Rationale: The current document upload component is a placeholder card with a text input. The feature needs institutional, document-centric styling and explicit validation states while staying aligned with the project's shared form infrastructure.
- Alternatives considered:
  - Use raw HTML file input inside the page component only: rejected because it weakens consistency and bypasses shared validation patterns.
  - Introduce a separate design system for document upload: rejected because it adds unnecessary UI abstraction for one flow.

## Multipart Upload Contract

- Decision: Change the upload contract from metadata-only payload to `multipart/form-data` with a required binary file and optional descriptive metadata fields derived server-side when possible.
- Rationale: The current `DocumentUploadRequest` accepts only `filename`, `mediaType`, and `sizeBytes`, which is insufficient for secure storage and downstream ingestion. Actual document ingestion requires the backend to receive the source file.
- Alternatives considered:
  - Continue sending filename metadata only: rejected because it cannot support secure storage or indexing.
  - Upload directly from frontend to storage and send only a storage token to the API: rejected for this feature because it expands scope and complicates tenant-safe validation.

## Document Indexer Boundary

- Decision: Introduce `App\Services\Documents\DocumentIndexerService` that encapsulates document text normalization, chunking, embedding generation, Qdrant payload creation, and vector upserts, using `AttachmentIndexerService` as the implementation pattern.
- Rationale: The repository already contains a mature attachment indexer with useful chunking and normalization logic, but it is attachment- and contract-specific. A dedicated document indexer keeps the document domain explicit and avoids leaking unrelated concepts into the new workflow.
- Alternatives considered:
  - Reuse `AttachmentIndexerService` directly for documents: rejected because its payload shape and skip rules are tied to attachment-specific fields and contract models.
  - Put indexing logic into `DocumentProcessingService`: rejected because orchestration and low-level indexing concerns should remain separate within the service layer.

## Chunk Persistence Strategy

- Decision: Persist one row per searchable chunk in `document_segments`, including tenant, document, segment index, normalized content, source label, and searchability flag, and use those rows as the relational source of truth for downstream retrieval references.
- Rationale: The `DocumentSegment` model and repository already exist, and relational persistence is necessary for source traceability, retry cleanup, and deterministic retrieval metadata.
- Alternatives considered:
  - Store chunks only in Qdrant payloads: rejected because it weakens relational traceability and makes replacement or retry cleanup harder to validate.
  - Store only whole-document extracted text: rejected because semantic retrieval and citations require chunk-level traceability.

## Qdrant Consistency Model

- Decision: Treat Qdrant as a derived index that is rebuilt from the current document version during processing, while document readiness is determined only after relational segment persistence and vector upsert both succeed.
- Rationale: This keeps the database as the workflow source of truth and ensures non-ready documents never become semantically searchable.
- Alternatives considered:
  - Mark the document ready immediately after chunk persistence and let embeddings finish later: rejected because it would violate the spec's retrieval-ready definition.
  - Make Qdrant the primary source of truth: rejected because document workflow, auditability, and replacement handling belong in the relational domain model.

## Workflow Orchestration

- Decision: Keep `DocumentProcessingService` as the owner of document state transitions, retry handling, index cleanup, indexer invocation, and audit emission, with controllers limited to request validation and response mapping.
- Rationale: The constitution requires centralized workflow validation and service-owned business rules. The existing document services already establish that layering.
- Alternatives considered:
  - Handle retries and processing directly in controllers: rejected because it violates the service boundary.
  - Put transition logic in repositories or models: rejected because it violates the constitution's domain and workflow rules.

## Test Strategy

- Decision: Start implementation with failing backend feature tests for upload, retry, authorization, and tenant isolation; backend unit tests for `DocumentIndexerService` and `DocumentProcessingService`; Angular component tests for the Formly upload UI; and Playwright coverage for the document upload flow.
- Rationale: The constitution requires test-first work across API, workflow, authorization, component, and E2E layers. The repository already has scaffolding in all of those areas, including a placeholder document-upload Playwright spec.
- Alternatives considered:
  - Rely on manual QA for indexing behavior first: rejected because it violates the test-first rule.
  - Add only backend tests: rejected because the upload experience includes required frontend validation and workflow messaging.
