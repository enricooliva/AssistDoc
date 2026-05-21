# Feature Specification: Document List Management

**Feature Branch**: `007-document-list`  
**Created**: 2026-05-21  
**Status**: Draft  
**Input**: User description: "L'utente deve poter vedere e consultare una lista paginata di documenti, con associati i loro tag e la persona che lo ha caricato. Deve inoltre essere possibile eliminare il singolo documento. L'eliminazione è sempre soft delete."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Browse Documents (Priority: P1)

An authenticated tenant user can view a paginated list of documents and inspect, for each document, its tags and the person who uploaded it.

**Why this priority**: The primary value of the feature is giving users a reliable way to find and inspect documents. Without the list view, the rest of the feature has no practical value.

**Independent Test**: Can be fully tested by signing in as an authorized tenant user, opening the document list, and confirming that the page shows document rows with pagination, tags, and uploader information.

**Acceptance Scenarios**:

1. **Given** a tenant has multiple documents, **When** an authorized user opens the document list, **Then** the system shows a paginated set of documents for that tenant.
2. **Given** a document has one or more tags, **When** the document appears in the list, **Then** the tags are visible alongside the document row.
3. **Given** a document was uploaded by a user, **When** the document appears in the list, **Then** the uploader is shown so the document source is identifiable.
4. **Given** the user navigates to another page of results, **When** the next page is loaded, **Then** the list updates to show the correct slice of documents without losing the current tenant context.

---

### User Story 2 - Soft Delete a Document (Priority: P2)

An authorized user can delete a single document, and the deletion is always a soft delete so the document is removed from the normal list without being permanently erased.

**Why this priority**: Deletion is the critical control action in this feature. It must be safe, reversible at the data-retention level, and clearly reflected in the user interface.

**Independent Test**: Can be fully tested by selecting a document, confirming deletion, and verifying that the document disappears from the normal list while remaining preserved as a soft-deleted record.

**Acceptance Scenarios**:

1. **Given** an authorized user is viewing a document row, **When** the user confirms deletion, **Then** the document is soft-deleted and no longer appears in the standard document list.
2. **Given** a document has already been soft-deleted, **When** a user attempts to delete it again, **Then** the system prevents the duplicate action and shows a clear message.
3. **Given** a user does not have permission to delete documents, **When** the user attempts to delete a document, **Then** the system denies the action and leaves the document unchanged.

---

### Edge Cases

- A document may have no tags; in that case, the list must still render the row and show an explicit empty-state label.
- The document list must remain usable when the current page contains fewer results than the configured page size.
- If a document is removed between page loads, the next refresh must not show the deleted record in the standard list.
- If the user tries to delete a document that no longer exists in the active list, the system must show a clear not-found or already-deleted message.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST provide an authenticated document list for each tenant.
- **FR-002**: The document list MUST be paginated.
- **FR-003**: Each document row in the list MUST show the document identity, associated tags, and the person who uploaded it.
- **FR-004**: The list MUST exclude soft-deleted documents from the normal browsing experience.
- **FR-005**: The system MUST allow an authorized user to soft-delete a single document.
- **FR-006**: Soft deletion MUST preserve the document record for retention and audit purposes while removing it from the normal document list.
- **FR-007**: The system MUST prevent repeated deletion of the same already soft-deleted document through a clear user-facing response.
- **FR-008**: The system MUST prevent unauthorized users from deleting documents.
- **FR-009**: The system MUST keep the tenant boundary intact so users only see documents that belong to their own tenant.
- **FR-010**: The document list MUST continue to show uploader and tag information after pagination changes.
- **FR-011**: The system MUST record who deleted a document and when the deletion occurred.

### API & Contract Requirements *(mandatory for API-backed features)*

- The feature MUST introduce or update versioned contracts for tenant-scoped document browsing and document deletion.
- The list contract MUST define pagination inputs, page metadata, and the fields returned for each document, including tags and uploader identity.
- The deletion contract MUST define the request shape, success response, unauthorized response, not-found response, and already-deleted response.
- Both contracts MUST define authentication requirements and authorization rules.
- Both contracts MUST define that deleted documents are handled as soft deletions and are excluded from the standard list response.

### Workflow & State Requirements *(mandatory when entities have lifecycle state)*

- `Document` MUST support at least the states `active` and `deleted`.
- Allowed transitions MUST be:
  - `active` -> `deleted`
- Soft deletion MUST be the only deletion path in this feature.
- The transition MUST record the actor, target document, timestamp, and prior state.
- A deleted document MUST remain preserved for traceability even though it no longer appears in the standard list.

### UI & Localization Requirements *(mandatory for frontend work)*

- All user-visible text for the document list and delete flow MUST be defined for Italian (`it-IT`).
- The document list MUST show pagination controls and clearly indicate the current page of results.
- The document list MUST show an empty state when no visible documents are available.
- The delete action MUST require a confirmation step that clearly explains the document will be soft-deleted.
- The interface MUST show a clear loading state while the list is being retrieved and while a delete action is in progress.
- The interface MUST show a clear error state if loading or deletion fails.

### Key Entities *(include if feature involves data)*

- **Document**: A tenant-owned record representing a stored file, its current visibility state, and its association to tags and uploader information.
- **Tag**: A label attached to a document for classification and browsing support.
- **User**: The person who uploaded a document or initiates a deletion action.
- **Tenant**: The ownership boundary that limits which documents a user can browse and delete.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 90% of authorized users can find and open the first page of the document list in under 10 seconds during acceptance testing.
- **SC-002**: 95% of document rows shown in acceptance testing display both tag information and uploader information without missing data.
- **SC-003**: 100% of deletion actions in acceptance testing remove the document from the normal document list without permanently erasing the underlying record.
- **SC-004**: 100% of unauthorized delete attempts are blocked and produce a clear user-facing denial.
- **SC-005**: 90% of users can complete the confirm-delete flow in a single attempt without assistance.

## Assumptions

- The feature applies to authenticated users operating within an existing tenant context.
- Pagination defaults to a reasonable page size chosen by the product team and remains consistent across the list view.
- Documents may have zero, one, or many tags.
- Soft-deleted documents are hidden from the standard list and are not part of the normal browsing flow.
- Restore or permanent purge flows are out of scope for this feature.
- The existing document ownership and user attribution data already exists and can be reused for the list view.

## Compliance Notes *(mandatory)*

- This feature follows the spec-driven workflow and keeps the scope limited to browsing and soft deletion.
- The tenant boundary must be enforced for every list and delete action so users cannot act on documents outside their own workspace.
- Soft delete is required to preserve traceability and avoid irreversible removal of business records; the simpler hard-delete alternative was rejected.
- Italian localization is mandatory for all visible list and delete interactions.
