# Data Model: Grounded Chat Responses

## Chat Conversation

**Purpose**: Tenant-scoped thread that groups one user's sequential questions and system responses.

**Core Fields**

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|-------|
| `id` | UUID/string identifier | Yes | Generated server-side | Public conversation identifier |
| `tenant_id` | UUID/string identifier | Yes | Must reference authenticated tenant | Isolation boundary |
| `user_id` | UUID/string identifier | Yes | Must reference authenticated user | Conversation owner |
| `title` | string | Yes | 1-120 chars | User-facing label |
| `status` | enum | Yes | `active`, `archived` | Conversation lifecycle |
| `last_message_at` | datetime | Yes | Server-generated and updated on every message | Used for ordering |

**Relationships**

- One `ChatConversation` belongs to one `Tenant`.
- One `ChatConversation` belongs to one `User`.
- One `ChatConversation` has many `ChatMessage` rows.

**Lifecycle Rules**

- Conversations begin as active.
- Conversations remain active while the user continues asking questions.
- Archiving is optional for later housekeeping and does not alter tenant isolation.

## Chat Message

**Purpose**: Individual user question or system response within a conversation.

**Core Fields**

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|-------|
| `id` | UUID/string identifier | Yes | Generated server-side | Message identifier |
| `tenant_id` | UUID/string identifier | Yes | Must match parent conversation tenant | Tenant safety |
| `conversation_id` | UUID/string identifier | Yes | Must reference existing conversation | Parent thread |
| `actor_type` | enum | Yes | `user`, `assistant` | Distinguishes message direction |
| `body` | text | Yes | Non-empty after trim | User question or final response |
| `response_state` | enum nullable | No | `answered`, `insufficient_information`, `failed` for assistant messages | Outcome state for system response |
| `created_at` | datetime | Yes | Server-generated | Chronological ordering |

**Relationships**

- One `ChatMessage` belongs to one `ChatConversation`.
- Assistant messages may have zero or more `MessageCitation` rows.

**Workflow Rules**

- The user question creates a submitted interaction in the service layer.
- The system resolves that interaction into an assistant response with one of the outcome states.
- Assistant responses with `answered` or `insufficient_information` are both valid terminal outcomes.
- Failed responses must preserve the question context without exposing foreign tenant content.
- API payloads expose these terminal outcomes through `assistantMessage.responseState`, while validation failures stay outside the message model in `error.fieldErrors`.

## Message Citation

**Purpose**: Visible supporting reference that connects a chat answer to tenant source material.

**Core Fields**

| Field | Type | Required | Validation | Notes |
|-------|------|----------|------------|-------|
| `id` | UUID/string identifier | Yes | Generated server-side | Citation identifier |
| `tenant_id` | UUID/string identifier | Yes | Must match parent chat message tenant | Tenant safety |
| `chat_message_id` | UUID/string identifier | Yes | Must reference assistant message | Owning response |
| `document_id` | UUID/string identifier | Yes | Must reference tenant document | Source document |
| `document_segment_id` | UUID/string identifier | Yes | Must reference supporting segment | Citation anchor |
| `quote_text` | text | Yes | Non-empty excerpt | Evidence shown in UI |
| `source_label` | string | Yes | Non-empty | Human-readable source location |
| `created_at` | datetime | Yes | Server-generated | Persistence time |

**Relationships**

- One `MessageCitation` belongs to one `ChatMessage`.
- Citations are derived from the grounded retrieval results used to answer the question.

## Question Outcome

**Purpose**: Logical record of whether a question was answered, declined for insufficient evidence, or failed.

**Representation**

- No separate persisted model is required for the first implementation.
- The outcome is represented by the assistant message `response_state`, the persisted citations, and the audit event for the question.

## Validation Rules by Operation

### Submit Question

- Question text is required.
- Question text must not be blank after trimming.
- Authenticated user must belong to the conversation tenant.
- Authenticated user must hold a role that permits chat access.

### Create Conversation

- Conversation title is optional.
- If omitted, a default title may be generated for the first release.

### Read History

- Only conversations and messages belonging to the authenticated tenant may be returned.
- Message order must follow `created_at`.

## Derived Read Models

### Conversation List Item

| Field | Source |
|-------|--------|
| `id` | `ChatConversation.id` |
| `title` | `ChatConversation.title` |
| `status` | `ChatConversation.status` |
| `lastMessageAt` | `ChatConversation.last_message_at` |

### Chat Thread View

| Field | Source |
|-------|--------|
| `conversation` | `ChatConversation` |
| `messages` | Ordered `ChatMessage` rows |
| `citations` | Ordered `MessageCitation` rows for assistant messages |
