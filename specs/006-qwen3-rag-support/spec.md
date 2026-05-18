# Feature Specification: Configured Qwen RAG Support

**Feature Branch**: `006-qwen3-rag-support`  
**Created**: 2026-05-13  
**Status**: Draft  
**Input**: User description: "Design a RAG system that loads retrieval profiles from configuration, uses Qwen as the default profile, supports flexible code-configured chunk-size profiles with token-based overlaps, and enforces model tokenizer limit checks during chunking."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Use the Configured Qwen Retrieval Profile (Priority: P1)

The system loads the retrieval model profile from code configuration and uses `Qwen` as the default profile, so document ingestion and retrieval remain consistent without operator-managed retrieval-profile CRUD or selectors.

**Why this priority**: The retrieval profile is a core system setting. Making it configuration-driven removes unnecessary runtime management and keeps the ingestion path stable while still allowing deployment-level changes.

**Independent Test**: Can be fully tested by setting the retrieval profile configuration to the default `Qwen` profile, processing a document, and confirming the resulting knowledge records keep the configured profile identity without any runtime profile-selection UI or endpoint.

**Acceptance Scenarios**:

1. **Given** the retrieval profile configuration declares `Qwen` as the default profile, **When** a document is uploaded, **Then** the system prepares the document using that configured profile without exposing retrieval-profile selection in the upload flow.
2. **Given** the retrieval profile configuration changes at deployment time, **When** the next preparation run starts, **Then** the system uses the configured active profile and preserves which profile identity was used for the resulting data.
3. **Given** the retrieval profile configuration is invalid or missing a default profile, **When** the system starts document preparation, **Then** the system fails fast with a clear configuration error rather than indexing with an ambiguous retrieval profile.

---

### User Story 2 - Automatically Embed with Three Chunking Profiles (Priority: P2)

When a user uploads a document, the system automatically starts embedding with the three configured chunking profiles `small`, `medium`, and `large`, each using its own overlap settings, so the upload flow does not need to expose profile selection.

**Why this priority**: Supporting the configured retrieval profile alone is not enough if document ingestion still depends on manual chunking selection. Automatic multi-profile embedding keeps the upload flow simple while preserving retrieval flexibility behind the scenes.

**Independent Test**: Can be fully tested by uploading a document and confirming that the system starts embedding runs for the three configured chunking profiles without showing profile selectors in the upload flow.

**Acceptance Scenarios**:

1. **Given** a user uploads a document, **When** the upload completes, **Then** the system automatically starts embedding runs with the `small`, `medium`, and `large` chunking profiles.
2. **Given** the upload flow is displayed, **When** the user loads the document page, **Then** the system does not expose chunking or retrieval profile selectors in that upload path.
3. **Given** a configured chunking profile is invalid, **When** the automatic embedding flow loads or uses that profile, **Then** the system rejects it with a clear validation message explaining the invalid token size or overlap rule.

---

### User Story 3 - Block Oversized Chunks Before Indexing (Priority: P3)

An operator can trust that no chunk exceeds the active model's tokenizer limit during preparation, so ingestion failures are caught early and retrieval data never contains model-incompatible content.

**Why this priority**: Model-aware limit enforcement protects ingestion quality, prevents silent retrieval defects, and is required for safe adoption of larger-context model profiles such as Qwen.

**Independent Test**: Can be fully tested by preparing documents that would naturally create oversized chunks and verifying that the system either re-splits them into compliant chunks or fails the preparation run with a clear reason before retrieval data is stored.

**Acceptance Scenarios**:

1. **Given** a document is being prepared under a selected retrieval model profile, **When** a generated chunk would exceed that profile's tokenizer limit, **Then** the system prevents that chunk from being indexed and resolves the condition through compliant re-splitting or a recorded failure.
2. **Given** a chunking profile is valid in general but too large for the selected retrieval model profile, **When** preparation starts, **Then** the system stops the run before retrieval data is published and explains which limit was violated.
3. **Given** a preparation run completes successfully, **When** the resulting searchable content becomes retrieval-ready, **Then** every stored chunk is within the tokenizer limit of the retrieval model profile used for that run.

### Edge Cases

- What happens when an operator selects a retrieval model profile that is no longer allowed for new preparation runs: the system prevents new use of that profile while preserving traceability for documents already prepared with it.
- What happens when a chunking profile uses a very small token size that would create an impractically high chunk count: the system warns or rejects the profile according to defined profile-validation rules before it is exposed for preparation use.
- How does the system handle a document section that cannot be split into a compliant chunk without losing all meaningful content boundaries: the preparation run fails with a user-readable reason and leaves the document non-ready for retrieval.
- What happens when an existing retrieval-ready document is reprocessed with a different model or chunking profile: older retrieval data is withdrawn before the newly prepared data becomes active.
- How does the system handle retrieval content prepared under different embedding dimensions: the system keeps profile-specific retrieval data isolated so incompatible semantic representations are never mixed in the same retrieval path.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST load supported retrieval model profiles from code configuration rather than requiring runtime retrieval-profile CRUD or selectors.
- **FR-002**: The system MUST provide a default retrieval profile named `Qwen` with a maximum input window of 40,000 tokens and semantic vector size of 4,096 values.
- **FR-003**: Each configured retrieval model profile MUST record, at minimum, its name, tokenizer identity, maximum token window, semantic vector size, availability status for new preparation runs, and whether it is the default profile.
- **FR-004**: The system MUST treat retrieval profile configuration as deployment-managed code configuration and MUST not expose runtime create, update, delete, or selection flows for retrieval profiles.
- **FR-005**: The system MUST support multiple named chunking profiles that define token-based chunk size and token-based overlap.
- **FR-006**: The configured chunking profiles MUST include at least `small`, `medium`, and `large`.
- **FR-007**: Each chunking profile MUST have a unique name and validation rules that reject non-positive chunk sizes, negative overlaps, or overlaps greater than or equal to the chunk size.
- **FR-008**: The system MUST automatically start embedding with the configured `small`, `medium`, and `large` chunking profiles when a document is uploaded or retried.
- **FR-009**: The system MUST apply the tokenizer associated with the configured retrieval model profile when measuring chunk size and overlap behavior.
- **FR-010**: The system MUST check every generated chunk against the configured retrieval model profile's tokenizer limit before that chunk is eligible for storage or indexing.
- **FR-011**: The system MUST ensure that no stored chunk exceeds the configured retrieval model profile's tokenizer limit.
- **FR-012**: When a generated chunk exceeds the configured retrieval model profile's tokenizer limit, the system MUST either re-split the content into compliant chunks or fail the preparation run before any non-compliant retrieval data is published.
- **FR-013**: The system MUST preserve traceability from each stored chunk to the preparation run, source document, retrieval model profile, and chunking profile used to create it.
- **FR-014**: The system MUST ensure retrieval queries use only semantic data prepared under a compatible retrieval model profile and vector size.
- **FR-015**: The system MUST require a new preparation run before an existing document can contribute retrieval data under a different retrieval model profile or chunking profile.
- **FR-016**: The system MUST withdraw outdated retrieval data before newly reprocessed retrieval data for the same document becomes active.
- **FR-017**: The system MUST record audit events for configuration-driven profile changes as deployed, document-preparation runs, token-limit validation failures, and profile-driven reprocessing.
- **FR-018**: Each audit event for this feature MUST record tenant when applicable, actor, affected profile or document, action performed, outcome, and timestamp.
- **FR-019**: The system MUST expose user-readable failure details when preparation cannot complete because of invalid profile settings, tokenizer-limit violations, or incompatible profile combinations.
- **FR-020**: The system MUST preserve historical visibility of which retrieval model profile and chunking profile were used for any retrieval-ready document.

### API & Contract Requirements *(mandatory for API-backed features)*

- The feature MUST introduce or extend versioned REST contracts for:
  - Uploading a document without retrieval-profile selectors
  - Listing chunking profiles and their availability status
  - Starting a document-preparation or reprocessing run with an explicit chunking profile when needed
  - Retrieving preparation-run details, including configured profile identity and validation failures
- Each contract MUST define request schema, response schema, validation rules, authentication requirements, authorization roles, and standardized error responses.
- Retrieval model profile configuration MUST be file-based and retained only for historical traceability in preparation-run records, not exposed as runtime selection APIs.
- Chunking profile contracts MUST define token-size rules, token-overlap rules, configured profile identity, and validation errors for invalid profile combinations.
- Preparation-run contracts MUST define how tokenizer-limit failures, incompatible profile selections, and successful profile-based reprocessing outcomes are represented.
- Any retrieval-facing contract that consumes prepared semantic data MUST define how it enforces compatibility between the query path and the retrieval model profile used to prepare stored chunks.
- All new or updated contracts created for this feature MUST be versioned before implementation begins.

### Workflow & State Requirements *(mandatory when entities have lifecycle state)*

- `Preparation Run` MUST support the states `queued`, `processing`, `failed`, and `ready`.
- Allowed `Preparation Run` transitions MUST be:
  - `queued` -> `processing`
  - `processing` -> `ready`
  - `processing` -> `failed`
  - `failed` -> `queued` for authorized reprocessing
- A preparation run MUST transition to `failed` if profile validation or tokenizer-limit enforcement cannot produce compliant retrieval data.
- A document can become retrieval-ready for a new profile combination only after its active preparation run reaches `ready`.
- Reprocessing a previously retrieval-ready document MUST withdraw prior active retrieval data before the new run transitions to `ready`.
- Audit trail data for every preparation-run transition MUST include actor, timestamp, prior state, new state, selected retrieval model profile, selected chunking profile, and failure rationale when applicable.

### Key Entities *(include if feature involves data)*

- **Retrieval Model Profile**: A code-configured semantic-processing definition that defines tokenizer identity, maximum token window, vector size, and whether the profile is allowed for new preparation runs.
- **Chunking Profile**: A code-configured preparation profile that defines token-based chunk size, token-based overlap, validation status, and selection identity for future runs.
- **Preparation Run**: A tracked document-processing attempt that applies one retrieval model profile and one chunking profile to create retrieval-ready chunks.
- **Searchable Chunk**: A stored content unit derived from a source document, validated against the selected tokenizer limit and linked to its originating preparation run.
- **Profile Compatibility Record**: The historical link showing which retrieval model profile and chunking profile produced the active retrieval data for a document.
- **Validation Failure**: A recorded reason explaining why a profile selection or generated chunk could not satisfy token-limit or compatibility rules.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of acceptance tests for the default configured `Qwen` profile confirm that documents can be prepared and marked retrieval-ready using a 40,000-token limit and 4,096-value semantic vectors.
- **SC-002**: 100% of default configured chunking profiles `small`, `medium`, and `large` can be selected for preparation runs and reused without manual data correction.
- **SC-003**: 100% of validation tests confirm that no stored chunk exceeds the tokenizer limit of the retrieval model profile used to prepare it.
- **SC-004**: 100% of incompatible profile-combination tests fail before non-compliant retrieval data becomes active.
- **SC-005**: 90% of reprocessing tests complete with clear historical traceability showing the prior and current retrieval model and chunking profiles for the same document.

## Assumptions

- The feature extends the existing authenticated, tenant-aware document-ingestion and semantic-retrieval workflows rather than creating a separate ingestion product.
- Operators are not responsible for selecting retrieval model profiles at runtime; the configured default profile is used by the system, while chunking profiles remain deployment-configured.
- The first release supports configuration-driven retrieval and chunking behavior plus compliant preparation behavior for server-side workflows; comparative retrieval-quality analytics across profiles are out of scope.
- Existing retrieval-ready documents may require explicit reprocessing before they can participate under a newly configured retrieval model profile such as Qwen.
- A document contributes retrieval data through one active profile combination at a time, even if historical preparation runs are retained for auditability.

## Compliance Notes *(mandatory)*

- This feature is constrained by the constitution's API-first design, service-layer workflow ownership, mandatory RBAC, contract versioning, centralized state transitions, and auditability requirements.
- Tokenizer-limit enforcement materially shapes the design because retrieval data must be validated before storage; the simpler alternative of allowing oversized chunks and relying on downstream failures was rejected.
- Supporting profile-specific semantic data adds complexity beyond a single global embedding configuration, but that complexity is required to prevent incompatible retrieval data from being mixed across model profiles.
