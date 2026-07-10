# Data Model: AssistDoc Private Document Assistant

## Tenant

**Purpose**: Company isolation boundary for all content, users, and audit activity.

**Fields**

- `id`: unique identifier
- `name`: company display name
- `slug`: unique tenant key
- `status`: active, suspended
- `created_at`
- `updated_at`

**Relationships**

- Has many `User`
- Has many `RoleAssignment`
- Has many `Document`
- Has many `ChatConversation`
- Has many `SearchQuery`
- Has many `AuditEvent`

**Validation**

- `name` required, 2-120 chars
- `slug` required, unique, lowercase URL-safe
- `status` required

## User

**Purpose**: Authenticated person operating within exactly one tenant in MVP scope.

**Fields**

- `id`
- `tenant_id`
- `email`
- `full_name`
- `auth_provider`: local, sso
- `status`: invited, active, disabled
- `last_login_at`
- `created_at`
- `updated_at`

**Relationships**

- Belongs to `Tenant`
- Has one current `RoleAssignment`
- Has many `ChatConversation`
- Has many `SearchQuery`
- Has many `AuditEvent`

**Validation**

- `email` required, normalized, unique within tenant
- `full_name` required, 2-120 chars
- `auth_provider` required
- `status` required

## RoleAssignment

**Purpose**: Explicit RBAC mapping for a user within a tenant.

**Fields**

- `id`
- `tenant_id`
- `user_id`
- `role`: super-admin, operator, viewer
- `assigned_by_user_id`
- `created_at`
- `updated_at`

**Relationships**

- Belongs to `Tenant`
- Belongs to `User`

**Validation**

- One active role assignment per user in MVP
- `role` must be one of the three supported values

## Document

**Purpose**: Uploaded file and its lifecycle state inside a tenant knowledge base.

**Fields**

- `id`
- `tenant_id`
- `uploaded_by_user_id`
- `filename`
- `media_type`
- `storage_path`
- `size_bytes`
- `status`: uploaded, queued, processing, indexed, failed
- `failure_reason`
- `uploaded_at`
- `last_status_at`
- `indexed_at`
- `created_at`
- `updated_at`

**Relationships**

- Belongs to `Tenant`
- Belongs to `User` as uploader
- Has many `DocumentSegment`
- Has many `AuditEvent`

**Validation**

- `filename`, `media_type`, `storage_path`, `status` required
- `size_bytes` must be positive
- `failure_reason` required when status is `failed`

**State Transitions**

- `uploaded` -> `queued`
- `queued` -> `processing`
- `processing` -> `indexed`
- `processing` -> `failed`
- `failed` -> `queued`

## DocumentSegment

**Purpose**: Searchable unit derived from a document and used as citation evidence.

**Fields**

- `id`
- `tenant_id`
- `document_id`
- `segment_index`
- `content_text`
- `source_label`
- `searchable`: true, false
- `created_at`
- `updated_at`

**Relationships**

- Belongs to `Tenant`
- Belongs to `Document`

**Validation**

- `segment_index` required, non-negative
- `content_text` required, non-empty
- `source_label` required

## ChatConversation

**Purpose**: Tenant-scoped thread for AI-assisted knowledge interaction.

**Fields**

- `id`
- `tenant_id`
- `user_id`
- `title`
- `status`: active, archived
- `last_message_at`
- `created_at`
- `updated_at`

**Relationships**

- Belongs to `Tenant`
- Belongs to `User`
- Has many `ChatMessage`

**Validation**

- `title` optional on create, generated from first question if absent
- `status` required

## ChatMessage

**Purpose**: One question or one answer inside a conversation.

**Fields**

- `id`
- `tenant_id`
- `conversation_id`
- `actor_type`: user, assistant
- `body`
- `response_state`: pending, completed, insufficient_information, failed
- `created_at`
- `updated_at`

**Relationships**

- Belongs to `Tenant`
- Belongs to `ChatConversation`
- Has many `MessageCitation`

**Validation**

- `actor_type` required
- `body` required, non-empty
- `response_state` required for assistant messages

## MessageCitation

**Purpose**: Source references attached to assistant messages.

**Fields**

- `id`
- `tenant_id`
- `chat_message_id`
- `document_id`
- `document_segment_id`
- `quote_text`
- `source_label`
- `created_at`

**Relationships**

- Belongs to `Tenant`
- Belongs to `ChatMessage`
- Belongs to `Document`
- Belongs to `DocumentSegment`

**Validation**

- At least one citation required for evidence-based assistant answers
- Citation tenant must match message tenant

## SearchQuery

**Purpose**: Standalone tenant-scoped semantic search interaction.

**Fields**

- `id`
- `tenant_id`
- `user_id`
- `query_text`
- `result_count`
- `created_at`

**Relationships**

- Belongs to `Tenant`
- Belongs to `User`

**Validation**

- `query_text` required, non-empty
- `result_count` integer, zero or more

## AuditEvent

**Purpose**: Immutable operational and security record.

**Fields**

- `id`
- `tenant_id`
- `actor_user_id`
- `event_type`
- `target_type`
- `target_id`
- `outcome`: success, failure, denied
- `metadata`
- `occurred_at`

**Relationships**

- Belongs to `Tenant`
- Optionally belongs to `User`

**Validation**

- `event_type`, `outcome`, and `occurred_at` required
- Tenant required for every event, even when actor is unauthenticated but tenant can be inferred

## Cross-Entity Rules

- Every entity except global system configuration is tenant-owned or tenant-derived.
- `viewer` may read search and chat data only within the current tenant.
- Document segments and citations are invalid unless their owning document is in `indexed` state.
- Audit events are append-only and never edited in place.
- Queue jobs must carry both `tenant_id` and `document_id` so retries preserve isolation.
