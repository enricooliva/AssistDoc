# Data Model: Document List Management

## Document

- **Purpose**: Represents a tenant-owned uploaded file shown in the document list and eligible for soft delete.
- **Key fields**:
  - `id`
  - `tenant_id`
  - `uploaded_by_user_id`
  - `filename`
  - `media_type`
  - `storage_path`
  - `tags` as an ordered unique list of tag strings
  - `size_bytes`
  - `status`
  - `failure_reason`
  - `uploaded_at`
  - `last_status_at`
  - `indexed_at`
  - `deleted_at`
  - `deleted_by_user_id`
- **Relationships**:
  - Belongs to one tenant
  - Belongs to one uploading user
  - Has many document segments
- **Validation rules**:
  - Tags must remain normalized as unique non-empty strings.
  - Deleted documents must be excluded from the normal list view.
  - A document can be deleted only once.
- **State notes**:
  - Existing processing states remain in place.
  - `deleted` is a terminal soft-delete state for browsing purposes.

## Document Segment

- **Purpose**: Represents a searchable chunk derived from a document and stored for retrieval coherence.
- **Key fields**:
  - `id`
  - `tenant_id`
  - `document_id`
  - `chunk_preparation_run_id`
  - `retrieval_model_profile_id`
  - `chunking_profile_id`
  - `segment_index`
  - `content_text`
  - `token_count`
  - `source_label`
  - `searchable`
  - `activated_at`
  - `retired_at`
- **Relationships**:
  - Belongs to one document
  - Belongs to one preparation run
  - Belongs to one retrieval model profile
  - Belongs to one chunking profile
- **Validation rules**:
  - Segment records tied to a deleted document must be retired and excluded from retrieval.
  - Segment identity must remain aligned with Qdrant payloads through `tenant_id` and `document_id`.

## Tag Value

- **Purpose**: A document classification label displayed in the list.
- **Key fields**:
  - String value stored on the document record
- **Relationships**:
  - Belongs to one document record as part of the `tags` collection
- **Validation rules**:
  - Empty tags are not allowed.
  - Duplicate tags are collapsed during normalization.

## Document List Page

- **Purpose**: A paginated result set returned to the UI for browsing tenant documents.
- **Key fields**:
  - `items`
  - `page`
  - `perPage`
  - `total`
- **Relationships**:
  - Each item references a document record and its uploader summary
- **Validation rules**:
  - Pagination values must be positive.
  - The page payload must never include soft-deleted documents.

