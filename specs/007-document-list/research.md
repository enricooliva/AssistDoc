# Research: Document List Management

## Decision 1: Use the existing document table as the browsing source of truth

- **Decision**: Build the paginated list directly from tenant-owned document records and include tags plus uploader information in each page response.
- **Rationale**: The project already stores document ownership, tags, uploader, and processing state on the document record. Reusing that source keeps the list consistent with upload and processing flows and avoids a parallel index for browsing.
- **Alternatives considered**:
  - Create a separate read model just for the list.
  - Join across an additional summary table for tags and uploader metadata.
  - Rejected because the current schema already contains the required data and the feature scope is limited to browsing and deletion.

## Decision 2: Add explicit soft-delete metadata on the document record

- **Decision**: Mark documents as deleted in the relational model and store deletion metadata on the document record so deleted items can be excluded from normal browsing while remaining traceable.
- **Rationale**: The feature requires soft delete only. Recording deletion time and deleter identity on the document itself gives clear auditability and keeps the document lifecycle visible without permanently erasing the record.
- **Alternatives considered**:
  - Hard delete the record and rely on audit logs.
  - Create a separate tombstone table.
  - Rejected because hard delete loses recovery context and a separate tombstone table adds unnecessary complexity for the current scope.

## Decision 3: Keep Qdrant coherent by deleting vectors for the same tenant/document boundary

- **Decision**: When a document is soft-deleted, retire its relational segments and remove all matching Qdrant points using the existing `tenant_id` + `document_id` filter boundary.
- **Rationale**: The stored chunks already carry tenant and document identity, so that boundary is the safest way to keep the vector store aligned with document lifecycle changes. The feature does not need a new chunk model; it needs the existing chunk identity preserved and cleaned up consistently.
- **Alternatives considered**:
  - Leave Qdrant points untouched after soft delete.
  - Delete points by profile-specific filters only.
  - Rejected because stale points would keep deleted content searchable, and profile-specific cleanup would miss the broader document boundary.

## Decision 4: Use a queued cleanup step for vector deletion

- **Decision**: Dispatch vector cleanup after the relational delete has been committed so the user-facing delete operation does not depend on the vector store being available inside the database transaction.
- **Rationale**: Qdrant is an external system and cannot participate in the SQL transaction. Queueing the cleanup preserves the soft-delete state immediately and gives the system a retry path for vector cleanup.
- **Alternatives considered**:
  - Perform Qdrant deletion synchronously inside the request.
  - Skip cleanup if Qdrant is temporarily unavailable.
  - Rejected because synchronous cleanup makes the delete operation brittle, and skipping cleanup would leave deleted chunks searchable.

## Decision 5: Reuse the existing document page in the Angular frontend

- **Decision**: Extend the current `documents` route and components to support pagination and delete actions.
- **Rationale**: The UI already has a document area, so the work is an evolution of the existing page rather than a new navigation surface.
- **Alternatives considered**:
  - Add a dedicated browsing route separate from uploads.
  - Split document list and delete into different pages.
  - Rejected because the current page already groups document operations in one place and the feature scope is narrow.

