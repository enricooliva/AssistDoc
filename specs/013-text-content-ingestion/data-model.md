# Data Model: Text Content Ingestion

## Entity: Document

**Description**: Existing tenant-scoped content record extended to represent either an uploaded file or directly pasted text.

**Fields**:

- `id`: Unique document identifier
- `tenant_id`: Tenant ownership boundary
- `uploaded_by_user_id`: User who created the content item
- `filename`: User-facing label shown in lists and citations
- `source_type`: Origin of the content item (`file`, `text`)
- `media_type`: Stored content type, such as `application/pdf`, `text/plain`, or `text/markdown`
- `storage_path`: Private storage location for the uploaded file or normalized pasted-text artifact
- `size_bytes`: Size of the stored artifact
- `tags`: Optional classification tags
- `status`: Lifecycle state (`queued`, `processing`, `ready`, `failed`, `deleted`)
- `failure_message`: Human-readable processing failure reason
- `active_retrieval_model_profile_id`: Retrieval profile used for the currently searchable output
- `active_chunking_profile_id`: Chunking profile used for the currently searchable output
- `active_preparation_run_id`: Latest successful or current preparation attempt
- `processed_at`: Timestamp when the document became searchable
- `deleted_at`: Soft-delete timestamp
- `deleted_by_user_id`: User who removed the content item

**Validation Rules**:

- `source_type` is required and limited to supported values.
- `filename` is required for both ingestion modes.
- `storage_path` is required after creation because both ingestion modes persist a private source artifact.
- `media_type` must match the stored artifact type.
- `status` transitions must follow the existing preparation workflow.

**Relationships**:

- Belongs to one `Tenant`
- Belongs to one creator `User`
- Belongs to one deleter `User` when deleted
- Has many `DocumentSegment`
- Has many `ChunkPreparationRun`
- Has many search references and citations through segment-based relations

## Entity: Direct Text Submission

**Description**: Request payload used to create a `Document` from copied and pasted text.

**Fields**:

- `sourceLabel`: User-facing title or short description for the text content
- `text`: Raw pasted text to normalize and persist
- `tags`: Optional tag list

**Validation Rules**:

- `sourceLabel` is required, trimmed, and length-limited for list rendering.
- `text` is required, trimmed, and must contain non-whitespace content.
- `text` must respect the configured maximum input length for safe processing.
- `tags` follow the same normalization rules already used for file uploads.

## Entity: Document Segment

**Description**: Searchable chunk derived from a document after normalization and chunking.

**Fields**:

- `id`: Unique segment identifier
- `document_id`: Owning document
- `tenant_id`: Tenant boundary
- `chunk_preparation_run_id`: Preparation run that produced the segment
- `retrieval_model_profile_id`: Embedding profile used to create the vector
- `chunking_profile_id`: Chunking strategy used to split the content
- `content`: Chunk text
- `chunk_index`: Stable order within the source document
- `token_count`: Token count metadata when available
- `is_searchable`: Flag used by semantic retrieval

**Behavior Notes**:

- Segments are produced identically for file uploads and direct text submissions.
- Deleting a document removes every segment tied to that document.

## Entity: Chunk Preparation Run

**Description**: Existing processing-attempt record for transforming a stored source artifact into searchable segments and embeddings.

**Fields**:

- `id`: Unique run identifier
- `document_id`: Target document
- `tenant_id`: Tenant scope
- `requested_by_user_id`: User who triggered the run
- `retrieval_model_profile_id`: Selected embedding profile
- `chunking_profile_id`: Selected chunking profile
- `status`: `queued`, `processing`, `ready`, or `failed`
- `failure_code`: Machine-readable failure code
- `failure_message`: Human-readable failure reason
- `started_at`: Processing start timestamp
- `completed_at`: Processing end timestamp

**Behavior Notes**:

- File uploads and pasted text both enter the same run lifecycle.
- Retry reuses the stored artifact and creates a new preparation run without requiring a new document record.

## Entity: Search Cleanup Scope

**Description**: Logical cleanup set applied when a document is deleted.

**Contents**:

- `document_segments`: All segments associated with the document
- `vector_points`: All Qdrant points derived from those segments, matched by document and collection size
- `message_citations` or equivalent search references tied to removed segments

**Rules**:

- Cleanup must execute for both `file` and `text` source types.
- Cleanup must complete before the deleted document can disappear from active views as successfully removed.
