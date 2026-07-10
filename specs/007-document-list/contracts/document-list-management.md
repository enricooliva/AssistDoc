# Document List Management Contract

## 1. List tenant documents

**Endpoint**: `GET /api/v1/documents`

**Purpose**: Return a paginated tenant-scoped list of visible documents with tags and uploader information.

**Authorization**:
- Required roles: `super-admin`, `operator`, `viewer`
- Authentication: JWT bearer token

**Query parameters**:
- `page` optional, positive integer, default `1`
- `perPage` optional, positive integer, default `25`

**Success response**: `200 OK`

```json
{
  "items": [
    {
      "id": "123",
      "filename": "manuale.pdf",
      "mediaType": "application/pdf",
      "sizeBytes": 12345,
      "tags": ["manuale", "tenant"],
      "status": "ready",
      "uploadedAt": "2026-05-21T08:10:00+00:00",
      "lastStatusAt": "2026-05-21T08:12:00+00:00",
      "indexedAt": "2026-05-21T08:12:00+00:00",
      "failureReason": null,
      "uploadedBy": {
        "id": "45",
        "fullName": "Operator Demo"
      }
    }
  ],
  "page": 1,
  "perPage": 25,
  "total": 1
}
```

**Rules**:
- Only documents belonging to the caller's tenant may be returned.
- Documents with the soft-delete state must be excluded from the `items` array.
- Each item must include the uploader summary and normalized tags.

**Error responses**:
- `401 Unauthorized` if the caller is not authenticated
- `403 Forbidden` if the role is not allowed

## 2. Soft-delete a document

**Endpoint**: `DELETE /api/v1/documents/{documentId}`

**Purpose**: Mark a single tenant document as deleted while keeping traceability and removing the document from the normal list.

**Authorization**:
- Required roles: `super-admin`, `operator`
- Authentication: JWT bearer token

**Path parameters**:
- `documentId` required, document identifier within the caller's tenant

**Success response**: `200 OK`

```json
{
  "documentId": "123",
  "status": "deleted",
  "deletedAt": "2026-05-21T08:15:00+00:00",
  "deletedBy": {
    "id": "45",
    "fullName": "Operator Demo"
  },
  "removedFromList": true
}
```

**Rules**:
- The operation must be soft delete only.
- The document record must remain preserved for traceability.
- The operation must retire associated segments and remove the matching Qdrant points for the same tenant/document boundary.
- A second delete attempt for the same document must return a clear conflict-style error.

**Error responses**:
- `401 Unauthorized` if the caller is not authenticated
- `403 Forbidden` if the role is not allowed
- `404 Not Found` if the document is outside the tenant or does not exist
- `409 Conflict` with code `ALREADY_DELETED` if the document was already soft-deleted

