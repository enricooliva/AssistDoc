# Research: Text Content Ingestion

## Decision 1: Add a dedicated text-ingestion endpoint instead of overloading the existing upload contract

**Decision**: Introduce a second authenticated create endpoint for direct text submission, while preserving the existing multipart upload endpoint for files.

**Rationale**: The current upload endpoint is file-centric and already validated as multipart input. A dedicated JSON or form-based endpoint keeps validation explicit, avoids a brittle mixed payload contract, and lets the frontend model the two user actions clearly.

**Alternatives considered**:

- Accept either `file` or `text` in the same endpoint.
  Rejected because it complicates validation, documentation, and error handling around mutually exclusive fields.
- Create a separate "notes" domain for pasted text.
  Rejected because the text must enter the same searchable document lifecycle and would duplicate processing logic.

## Decision 2: Store pasted text in private storage and reuse the existing extraction pipeline

**Decision**: Persist pasted text as a private plain-text artifact referenced by `documents.storage_path`, then process it through the same preparation flow used by uploaded files.

**Rationale**: The existing architecture already treats private storage plus a `Document` record as the source of truth for downstream extraction, chunking, retry, and deletion. Reusing that pattern keeps security and cleanup consistent.

**Alternatives considered**:

- Store the raw pasted text only in a database column.
  Rejected because it would create a second canonical source path and special-case extraction, retry, and deletion behavior.
- Skip persistence and generate segments directly from the submitted text.
  Rejected because retries, audits, and later debugging need a stable source artifact.

## Decision 3: Extend `Document` with a source-type discriminator

**Decision**: Add a persisted `source_type` field on `documents` with at least `file` and `text`.

**Rationale**: The UI, API contract, and audit trail need to distinguish the origin of a content item, and the spec explicitly requires the platform to do so.

**Alternatives considered**:

- Infer the source type from MIME type alone.
  Rejected because both uploaded `.txt` files and pasted text can resolve to `text/plain`, which would be ambiguous.
- Derive the source type from the presence of `storage_path`.
  Rejected because both ingestion modes still use private storage.

## Decision 4: Use the existing document area and Formly form rather than a new screen

**Decision**: Extend the current Angular document upload component so the user can choose between file upload and direct text entry within the same feature area.

**Rationale**: Users already go to the documents section to add searchable content. Keeping both ingestion modes there preserves navigation and minimizes frontend drift.

**Alternatives considered**:

- Build a second route for text ingestion.
  Rejected because it fragments a single user intent across two screens.
- Replace the current form with bespoke controls outside Formly.
  Rejected because the project standard is Bootstrap plus `ngx-formly`, and the current component already follows it.

## Decision 5: Keep deletion semantics document-centric and remove search artifacts for every source type

**Decision**: The delete flow must continue to remove the document record from active views and also remove all related segments, search references, and vector points regardless of whether the source started as a file or pasted text.

**Rationale**: Searchability is attached to the document lifecycle, not to the ingestion mechanism. Keeping one delete contract avoids source-specific data leaks in Qdrant or citation tables.

**Alternatives considered**:

- Soft-delete only the document metadata and leave vectors for asynchronous cleanup.
  Rejected because the active requirement is immediate removal from semantic retrieval.
- Apply different cleanup rules to text submissions.
  Rejected because it would create inconsistent privacy and tenant-isolation behavior.
