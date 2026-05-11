# Research: Grounded Chat Responses

## Retrieval-First Chat Orchestration

- Decision: Keep `ChatService` as the orchestration layer and make it call semantic retrieval before response generation.
- Rationale: The current backend already routes chat requests through `ChatController -> ChatService`, and the constitution requires business rules to live in Services. Retrieval-first orchestration keeps the flow centralized and makes tenant isolation enforceable in one place.
- Alternatives considered:
  - Put retrieval directly in the controller: rejected because it would violate the service boundary.
  - Bypass the existing chat service with a new endpoint layer: rejected because it would duplicate route and authorization logic.

## Qdrant as Grounding Source

- Decision: Use Qdrant search results as the grounding evidence for each chat answer and filter every retrieval by tenant before the answer is composed.
- Rationale: The feature needs tenant-safe grounding, and the repository already uses Qdrant for document retrieval. Treating Qdrant as the evidence source keeps chat aligned with the document-ingestion workflow.
- Alternatives considered:
  - Query the relational database directly for answer text: rejected because it weakens semantic retrieval and citation quality.
  - Mix tenant and global retrieval results: rejected because it risks cross-tenant leakage.

## Llama3.2 Response Generation

- Decision: Generate the final answer through the existing AI completion abstraction, configured for `llama3.2`, after the most relevant tenant evidence has been gathered.
- Rationale: The user asked for an elaborate response from `llama3.2`, and the repo already has a `ChatCompletionService` placeholder that can be upgraded without changing the controller contract.
- Alternatives considered:
  - Return only raw search snippets: rejected because the feature explicitly needs an elaborate chat response.
  - Hard-code model-specific prompts inside controllers: rejected because it would spread AI concerns outside Services.

## Citation Mapping

- Decision: Persist citations separately from the message body and derive them from the retrieved evidence so the UI can show which tenant sources supported the answer.
- Rationale: The current code already exposes a citation panel, and separate citation records keep answer text cleaner while preserving traceability back to documents and document segments.
- Alternatives considered:
  - Embed citations as free text in the answer body: rejected because it makes verification and UI rendering harder.
  - Store citations only in logs: rejected because users need visible evidence in the chat flow.

## Insufficient Information Handling

- Decision: Return an explicit insufficient-information outcome whenever retrieval support is missing or too weak to justify an answer.
- Rationale: The spec requires safe fallback behavior and tenant trust. A refusal is better than an unsupported answer when evidence is weak.
- Alternatives considered:
  - Always generate a best-effort answer: rejected because it risks hallucination and weakens trust.
  - Hide the question instead of responding: rejected because users need a visible outcome in chat.

## Conversation Persistence

- Decision: Store chat conversation history and messages in relational tables and keep the chat thread ordered by creation time and last activity.
- Rationale: The current frontend already shows a conversation rail and message thread. Persisted history is needed for follow-up questions, auditing, and tenant isolation.
- Alternatives considered:
  - Keep only in-memory chat state: rejected because it would break reloads and auditability.
  - Collapse all chat activity into one log row: rejected because it loses the conversation structure required by the UI.

## Frontend Binding

- Decision: Keep the existing chat page and citation panel, but replace demo data with API-backed conversation and message data.
- Rationale: The UI scaffold is already present and matches the product direction. Reusing it reduces churn while preserving a clear chat layout.
- Alternatives considered:
  - Build a new assistant page from scratch: rejected because it duplicates the existing chat slice.
  - Hide citations behind a separate page: rejected because the user needs evidence directly in context.
