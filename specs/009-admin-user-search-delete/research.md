# Research: Administrative User Search and Soft Delete

## Decision 1: Extend the existing `/api/v1/users` list endpoint with a search query parameter

**Decision**: Reuse the current paginated enterprise-user list endpoint and add
search input as an optional query parameter rather than creating a separate
search endpoint.

**Rationale**: The current user-management flow is already list-centric and
tenant-scoped. Reusing the existing endpoint preserves pagination semantics,
keeps frontend state simple, and matches the requirement to remain coherent
with the current implementation.

**Alternatives considered**:

- Create a dedicated `/api/v1/users/search` endpoint.
  Rejected because it would duplicate list behavior and fragment pagination and
  authorization rules.
- Perform search entirely on the client.
  Rejected because tenant-scoped filtering and soft-delete exclusion must
  remain server-side.

## Decision 2: Model deletion using Laravel soft delete semantics on `users`

**Decision**: Add soft delete support directly to the `users` entity with
`deleted_at` and `deleted_by_user_id`, and exclude deleted users from standard
enterprise-user list and auth lookups by default.

**Rationale**: The project already uses soft delete patterns for documents.
Applying the same model to users preserves auditability and keeps historical
relations intact while removing deleted users from standard operational flows.

**Alternatives considered**:

- Represent deletion only as a lifecycle status such as `deleted`.
  Rejected because existing list and auth lookups already rely on active record
  semantics, and status-only deletion would require every query to manually
  exclude deleted users.
- Hard delete the user record.
  Rejected because it would violate traceability requirements and risk breaking
  references from audit, lifecycle, and authentication history.

## Decision 3: Return conflict on repeated delete attempts

**Decision**: A second delete attempt against an already soft-deleted user will
return a conflict-style outcome with a standardized domain error.

**Rationale**: This matches the existing document delete behavior, makes the
operation explicitly non-idempotent at the business level, and gives the admin
clear feedback that the user was already removed from the active workflow.

**Alternatives considered**:

- Return success for repeated delete requests.
  Rejected because it hides operational state and weakens admin feedback.
- Return not found for already deleted users.
  Rejected because the original target exists historically and a conflict gives
  more precise lifecycle information.

## Decision 4: Soft-deleted users are excluded from authentication and standard admin listing by default

**Decision**: Once a user is soft deleted, they are excluded from the standard
admin list/search and from active authentication restoration flows.

**Rationale**: The feature is meant to remove users from the active
administration path, not merely mark them inactive. This keeps admin UX clear
and avoids ambiguous states where a deleted user is still operable.

**Alternatives considered**:

- Keep deleted users visible in the standard list with a flag.
  Rejected because the feature asks for deletion from the active flow and would
  blur the distinction between deactivated and deleted users.
- Add deleted-user recovery/review in the same change.
  Rejected because it expands scope beyond the requested search and soft delete
  behavior.

## Decision 5: Preserve pagination coherence by resetting or backfilling page state after search and deletion

**Decision**: Search starts from the first page, and delete/list refresh logic
must avoid leaving the administrator on an empty page when results shrink.

**Rationale**: This keeps the current list predictable and prevents the common
admin failure mode where the user sees an empty page despite remaining matches
on earlier pages.

**Alternatives considered**:

- Preserve the current page blindly after every search or delete.
  Rejected because it can strand the UI on an empty page after filtering or
  removing the last visible record.
- Rebuild the entire list interaction as infinite scroll.
  Rejected because it breaks coherence with the already implemented paginated
  administration area.
