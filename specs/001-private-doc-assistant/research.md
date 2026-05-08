# Research: AssistDoc Private Document Assistant

## Authentication Strategy

- Decision: Use stateless JWT bearer authentication for the API, with local username/password sign-in available for development and a pluggable external SSO path for non-local environments.
- Rationale: This matches the constitution exactly, keeps the Angular SPA independent from backend session storage, and allows one auth contract across local and hosted environments.
- Alternatives considered:
  - Session-cookie authentication: rejected because it conflicts with the constitution's stateless requirement.
  - Separate local and hosted auth APIs: rejected because it would fragment contracts and increase test surface.

## Tenant Isolation Enforcement

- Decision: Resolve the tenant from the authenticated principal and enforce tenant ownership in every controller entry point, service call, repository query, queue job, audit event, and vector-search request.
- Rationale: Tenant isolation is the platform's primary invariant and cannot rely on client-supplied identifiers or frontend route state.
- Alternatives considered:
  - Frontend-selected tenant context: rejected because client state is insufficient for security boundaries.
  - Repository-only tenant filters: rejected because service-level workflow checks and background jobs would remain exposed to cross-tenant mistakes.

## Authorization Model

- Decision: Implement RBAC with `super-admin`, `operator`, and `viewer`, with endpoint-level role declarations and service-level business checks for sensitive actions.
- Rationale: The constitution mandates these roles and requires endpoint-level authorization. Service checks are still needed for workflow actions such as upload retry and audit access.
- Alternatives considered:
  - Attribute-based policies only: rejected for MVP because the spec already defines fixed roles and ABAC adds complexity without immediate value.
  - UI-only hiding of privileged actions: rejected because authorization must be server-side.

## Chat Experience

- Decision: Build a simple ChatGPT-style Angular chat interface with a narrow conversation rail, central message thread, bottom composer, citation drawer, and empty/loading/error states in Italian.
- Rationale: The user explicitly requested a simple ChatGPT-like interface, and this layout fits the primary workflow of asking questions while keeping citations visible and reducing navigational friction.
- Alternatives considered:
  - Dashboard-first landing page with chat as a secondary widget: rejected because it weakens the primary user journey.
  - Heavier document-management-first UI: rejected because it slows access to the core chat experience for viewers.

## Document Ingestion Workflow

- Decision: Treat upload and indexing as separate phases. Upload creates the document record and stores the file; background processing extracts text, segments content, generates embeddings, indexes searchable units, and updates document state.
- Rationale: This preserves responsiveness, supports retries, and aligns with the specification's asynchronous processing and workflow-state requirements.
- Alternatives considered:
  - Fully synchronous upload and indexing: rejected because it blocks users and creates fragile long-running requests.
  - Manual indexing trigger after upload: rejected because it adds operational friction to the MVP.

## Search and Citation Model

- Decision: Use one retrieval path for both semantic search and chat grounding, with document segments as the citation unit and source references attached to responses.
- Rationale: Reusing the retrieval model reduces drift between search and chat and ensures citations point to the same document segments users can inspect directly.
- Alternatives considered:
  - Separate search and chat indexes: rejected because it duplicates data and increases consistency risk.
  - Whole-document citations only: rejected because they are too coarse to support user trust in answers.

## Audit Logging Scope

- Decision: Record immutable audit events for authentication outcomes, authorization denials, document lifecycle events, knowledge queries, and privileged audit-log access.
- Rationale: This covers the MVP's required oversight surface without turning the first release into a full event-sourcing system.
- Alternatives considered:
  - Log only administrative actions: rejected because failed sign-ins and denied access attempts are security-critical.
  - Log every UI interaction: rejected because it creates noise and weakens operational usefulness.

## Contract Format

- Decision: Define a single versioned OpenAPI document for the MVP under `contracts/assistdoc-openapi.yaml`.
- Rationale: One contract file is enough for the current scope, keeps auth and tenant rules visible in one place, and supports contract-first implementation and testing.
- Alternatives considered:
  - Separate contract file per feature area: rejected for MVP because the surface is still small enough to manage centrally.
  - Informal endpoint notes in markdown only: rejected because the constitution requires formal schemas and error formats.
