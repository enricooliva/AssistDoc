# Data Model: Secure Document Ingestion

## Document

**Purpose**: Tenant-owned source file tracked through secure storage, processing, failure, retry, and retrieval readiness.

**Core Fields**

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|-------|
| `id` | UUID/string identifier | Yes | Generated server-side | Public document identifier |
| `tenant_id` | UUID/string identifier | Yes | Must reference owning tenant | Tenant isolation boundary |
| `uploaded_by_user_id` | UUID/string identifier | Yes | Must reference authenticated uploader | Audit and ownership context |
| `filename` | string | Yes | 1-255 chars | Original client filename |
| `media_type` | string | Yes | Allowed uploaded MIME types only | Derived from validated upload |
| `storage_path` | string | Yes | Non-empty private storage path | Must not be client-controlled |
| `size_bytes` | integer | Yes | `> 0` and within configured limit | Derived from uploaded file |
| `status` | enum | Yes | `accepted`, `queued`, `processing`, `ready`, `failed` | Workflow state |
| `failure_reason` | string nullable | No | Nullable when not failed | User-readable reason for failure |
| `uploaded_at` | datetime | Yes | Server-generated | Initial acceptance timestamp |
| `last_status_at` | datetime | Yes | Updated on every transition | Status freshness indicator |
| `indexed_at` | datetime nullable | No | Set only on `ready` | Retrieval readiness timestamp |
| `replaced_by_document_id` | UUID/string nullable | No | Nullable | Optional linkage for replacement flows |

**Relationships**

- One `Document` belongs to one `Tenant`.
- One `Document` is uploaded by one `User`.
- One `Document` has many `DocumentSegment` rows.
- One `Document` may supersede an older `Document` version in replacement workflows.
- For `application/pdf` documents, extracted text is derived from the stored binary during processing and is never trusted from client-supplied metadata.

**State Transitions**

| From | To | Trigger | Responsible Service | Notes |
|------|----|---------|---------------------|-------|
| `accepted` | `queued` | Upload transaction completes | `DocumentService` or `DocumentProcessingService` | Ready for async processing |
| `queued` | `processing` | Worker starts ingestion | `DocumentProcessingService` | Audit required |
| `processing` | `ready` | Segments and embeddings stored successfully | `DocumentProcessingService` | Sets `indexed_at` |
| `processing` | `failed` | Extraction, chunking, persistence, or embedding fails | `DocumentProcessingService` | Records failure reason |
| `failed` | `queued` | Authorized retry request | `DocumentService` delegating to `DocumentProcessingService` | Clears stale failure state |

## Document Segment

**Purpose**: Searchable content unit derived from a document and used as the traceable unit for semantic retrieval and citations.

**Core Fields**

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|-------|
| `id` | UUID/string or integer identifier | Yes | Generated server-side | Internal persistence key |
| `tenant_id` | UUID/string identifier | Yes | Must match parent document | Tenant safety for queries |
| `document_id` | UUID/string identifier | Yes | Must reference existing document | Parent source |
| `segment_index` | integer | Yes | `>= 0` and unique within document version | Order within source |
| `content_text` | text | Yes | Non-empty normalized text | Indexed chunk body |
| `source_label` | string | Yes | Non-empty | Page/range/source location shown to users |
| `searchable` | boolean | Yes | Defaults false until ingestion completes | Guards partial indexing exposure |
| `embedding_model` | string | No | Nullable | Useful for reindex decisions |
| `vector_point_id` | string/integer | No | Nullable until vector stored | Link to Qdrant point |
| `created_at` | datetime | Yes | Server-generated | Segment persistence time |

**Relationships**

- Many `DocumentSegment` rows belong to one `Document`.
- Many `DocumentSegment` rows map to vector points in Qdrant.

**Lifecycle Rules**

- Segments are created during processing.
- For PDF uploads, segments are created from extracted content stream text after binary parsing succeeds.
- Segments remain non-searchable until the full ingestion run succeeds.
- Retry or replacement removes or supersedes stale segments before new ones become searchable.

## Ingestion Run

**Purpose**: Logical processing attempt for one document, whether initial upload or retry.

**Representation**

- No separate model is required for the first implementation if the workflow can be derived from document state, audit events, and segment refresh behavior.
- The service layer must still treat each processing attempt as a distinct run for logging, cleanup, and failure handling.

**Tracked Attributes in Service/Audit Scope**

| Attribute | Meaning |
|-----------|---------|
| `document_id` | Document being processed |
| `tenant_id` | Tenant boundary for cleanup and indexing |
| `triggered_by_user_id` | Actor for manual retry or replacement requests when available |
| `attempt_number` | Logical retry count if tracked |
| `started_at` | Processing start timestamp |
| `completed_at` | Processing completion timestamp |
| `outcome` | `ready` or `failed` |
| `failure_reason` | User-readable failure message |

## Semantic Retrieval Record

**Purpose**: Derived vector representation of one document segment stored in Qdrant.

**Payload Fields**

| Field | Required | Notes |
|-------|----------|-------|
| `tenant_id` | Yes | Used for tenant-scoped retrieval filters |
| `document_id` | Yes | Parent document reference |
| `segment_id` | Yes | Stable link back to relational segment |
| `segment_index` | Yes | Deterministic ordering |
| `filename` | Yes | User-facing source identification |
| `source_label` | Yes | Citation location |
| `content_text` | Yes | Retrieval snippet payload |
| `embedding_model` | Yes | Index provenance |
| `document_status` | Yes | Should be `ready` for searchable points |

**Rules**

- Vector records are derived data, not the workflow source of truth.
- Non-ready documents must not have active searchable vector points.
- Replacement or retry must remove stale vector points before activating new ones.

## Validation Rules by Operation

### Upload

- Uploaded file is required.
- File size must be greater than zero and within configured limits.
- MIME type and extension must be allowed.
- Authenticated user must hold `super-admin` or `operator`.

### Retry

- Target document must belong to the authenticated tenant.
- Authenticated user must hold `super-admin` or `operator`.
- Only `failed` documents are eligible for retry.

### Show/List

- Only tenant-owned documents are returned.
- `viewer` can read but cannot trigger workflow changes.

## Derived Read Models

### Document List Item

| Field | Source |
|-------|--------|
| `id` | `Document.id` |
| `filename` | `Document.filename` |
| `status` | `Document.status` |
| `uploadedAt` | `Document.uploaded_at` |
| `lastStatusAt` | `Document.last_status_at` |
| `uploadedBy` | User projection |
| `failureReason` | `Document.failure_reason` when relevant |

### Document Detail

| Field | Source |
|-------|--------|
| `document` | `Document` mapped for API |
| `segmentsCount` | Count of related `DocumentSegment` rows |
| `searchableSegmentsCount` | Count of related searchable segments |
| `latestAuditEvents` | Audit projection if included |
