# Quickstart: Grounded Chat Responses

## Goal

Verify that a tenant user can ask a question in chat, receive a tenant-grounded answer elaborated by `llama3.2`, and inspect the supporting references used to justify that answer.

## Prerequisites

- Backend dependencies installed and application configured.
- Frontend dependencies installed and Angular app configured.
- Qdrant available and seeded with tenant document vectors.
- `llama3.2` available through the configured chat-completion backend.
- Auth seed data available with at least one `operator` or `viewer` user and at least one tenant with searchable documents.

## Local Run

1. Start the backend application from `backend/`.
2. Start the frontend application from `frontend/`.
3. Ensure Qdrant is reachable from the backend environment.
4. Ensure the chat-completion backend can access `llama3.2`.
5. Confirm document ingestion has completed for at least one tenant so chat has grounding material.

## Manual Verification Flow

### Grounded Answer

1. Sign in as an authenticated tenant user.
2. Open the chat page.
3. Select or create a conversation.
4. Ask a question that matches tenant knowledge.
5. Confirm the response is elaborated in Italian and includes visible supporting references.
6. Confirm the references point back to tenant-owned documents, not foreign content.

### Insufficient Evidence

1. Ask a question that the tenant knowledge base cannot support.
2. Confirm the response clearly states that the available evidence is insufficient.
3. Confirm the system does not invent unsupported claims.

### Tenant Isolation

1. Sign in as a user from tenant A and ask a question.
2. Sign in as a user from tenant B.
3. Confirm tenant B cannot access tenant A conversations, answers, or supporting references.

### Conversation History

1. Ask multiple follow-up questions in the same chat.
2. Confirm the thread preserves message order and the latest cited answer appears in the citation panel.

## Required Automated Tests Before Implementation Completion

- Backend feature tests for conversation creation, question submission, cited answer payloads, and insufficient-information outcomes.
- Backend unit tests for retrieval-first orchestration and citation mapping from search results.
- Backend authorization and tenant-isolation tests for chat conversation access.
- Angular component tests for chat composition, citation display, loading state, and insufficient-information messaging.
- Playwright E2E tests for grounded chat answers and blocked cross-tenant access.
- Validation payloads return `error.fieldErrors` for blank questions, while successful answers use `answered`, `insufficient_information`, or `failed` in `assistantMessage.responseState`.
