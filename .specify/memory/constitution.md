<!--
Sync Impact Report
- Version change: template -> 1.0.0
- Modified principles: template placeholders replaced with project-specific governance
- Added sections: Purpose; Spec-Driven Workflow (Mandatory); Architectural Principles; Domain Rules & Invariants; Workflow & State Machine; Contract-First Development; Test-First Enforcement; Transactions & Consistency; Security; Frontend Architecture; UI & Design System; Quality & CI; Simplicity & Design Constraints; Governance; Authority
- Removed sections: none
- Templates requiring updates:
  - ✅ updated: .specify/templates/plan-template.md
  - ✅ updated: .specify/templates/spec-template.md
  - ✅ updated: .specify/templates/tasks-template.md
  - ⚠ pending: .specify/templates/commands/*.md (directory not present in this repository)
- Follow-up TODOs: none
-->
# AssistDoc Constitution

## 0. Purpose

This constitution defines the non-negotiable principles governing how the
platform is specified, designed, implemented, tested, and evolved.

- Specifications are the source of truth; code exists only to implement
  specifications.
- Clarity beats speed.
- Verification beats assumption.

This document is authoritative for all Spec Kit phases and implementation
decisions.

## 1. Spec-Driven Workflow (Mandatory)

All feature development MUST follow this sequence:

1. `/speckit.specify` to define requirements and user stories
2. Clarification to resolve all ambiguities
3. `/speckit.plan` to define architecture and approach
4. `/speckit.tasks` to derive executable tasks
5. `/speckit.implement` to implement strictly against tasks

### Rules

- No implementation without a specification.
- No planning with unresolved clarification markers.
- Every task MUST trace back to at least one requirement or user story.
- Specifications MUST be testable, complete, and internally consistent.

## 2. Architectural Principles

### 2.1 API First

- All functionality MUST be exposed through REST APIs.
- Backend components MUST NOT depend on frontend implementation details.
- Frontend code MUST NOT bypass backend APIs.

### 2.2 Layered Architecture

Backend structure MUST follow:

```text
Controller -> Service -> Repository -> Model
```

- Controllers MUST perform input validation and response mapping only.
- Services MUST contain business logic, orchestration, workflow rules, and
  transaction boundaries.
- Repositories MUST handle persistence only.
- Models MUST represent data structures and simple helpers only.

### 2.3 Domain Driven Design

- The domain MUST be modeled with Entities, Services, and Repositories.
- Entities MUST carry identity and state.
- Domain concepts MUST use a single canonical representation across the system.

## 3. Domain Rules & Invariants

- All business rules MUST live in Services.
- Controllers and Repositories MUST NOT contain business logic.
- Domain state MUST have a single source of truth.
- State changes MUST occur only through Services.

## 4. Workflow & State Machine

### Requirements

Workflow-oriented entities MUST define:

- States
- Allowed transitions
- Responsible roles
- Side effects

### Rules

- No direct state mutation is permitted.
- Transitions MUST be validated centrally.
- Invalid transitions MUST return standardized errors.

### Auditability

All transitions MUST record:

- Actor
- Timestamp
- From -> To
- Optional rationale

## 5. Contract-First Development

Before implementation, every API MUST define:

- Request schema
- Response schema
- Validation rules
- Error formats
- Authentication rules
- Authorization rules

Contracts are mandatory and versioned.

## 6. Test-First Enforcement

Implementation MUST NOT begin before the relevant tests exist and are expected
to fail.

### Backend

- API tests covering success and failure cases are mandatory.
- Authorization tests are mandatory.
- Workflow transition tests are mandatory.

### Frontend

- Component tests are mandatory for implemented UI behavior.
- Playwright end-to-end tests are mandatory for critical user flows.

## 7. Transactions & Consistency

- Multi-write operations MUST be transactional.
- Transactions MUST be controlled at the Service level.
- External integrations MUST NOT leave persisted state inconsistent.

## 8. Security

### Authentication

- The platform MUST use a stateless architecture.
- JWT bearer tokens are the only supported authentication tokens.
- SSO is mandatory outside local development environments.
- Local username/password login is allowed only for development.

### Authorization

- Role-Based Access Control (RBAC) is mandatory.

Minimum roles:

- `super-admin`
- `operator`
- `viewer`

Rules:

- Every endpoint MUST declare required roles.
- `viewer` is read-only unless a specification explicitly states otherwise.

## 9. Frontend Architecture

### Stack

- Angular SPA

### Modules

- `contracts`
- `dashboard`
- `users`
- `shared/core`

### Rules

- Frontend code MUST consume APIs only.
- Backend data and workflow state are the single source of truth.

## 10. UI & Design System

### Design Baseline

- Bootstrap is the primary design system.

### Components

- Forms MUST use `ngx-formly`.
- Tables MUST use `ngx-datatable`.

### Requirements

- The UI MUST present an institutional, document-centric experience.
- Typography and spacing MUST remain consistent across modules.
- Layouts MUST be responsive.

Forms MUST include:

- Validation
- Error messages
- Loading states

Tables MUST include:

- Pagination
- Filtering
- Sorting

### Localization

- All UI text MUST be written in Italian (`it-IT`).
- Validation, workflow, and server error messages MUST be Italian-ready.

## 11. Quality & CI

Code standards:

- PHP code MUST comply with PSR-12.
- Angular code MUST comply with the Angular style guide.

CI MUST run:

- Lint
- Tests
- Build

No merge is permitted while CI is failing.

## 12. Simplicity & Design Constraints

- Teams MUST start with the simplest solution that satisfies the specification.
- Speculative features are prohibited.
- Unnecessary abstraction is prohibited.

Any added complexity MUST include:

- Justification
- Simpler alternative considered and rejected
- Rollback strategy

## 13. Governance

### Amendment Rules

Changes to this constitution require:

- Written rationale
- Impact analysis
- Maintainer approval

### Versioning

- MAJOR: breaking governance changes or incompatible principle redefinitions
- MINOR: new principles, new sections, or materially expanded guidance
- PATCH: clarifications, wording improvements, and non-semantic refinements

### Compliance

- Every implementation plan MUST include a constitution check before research
  and after design.
- Every specification, plan, task list, and implementation review MUST verify
  compliance with this constitution.
- Violations MUST be rejected unless explicitly justified and approved.

## 14. Authority

This constitution supersedes any conflicting local rules or informal practices.

All specifications, plans, and implementations MUST comply with this document.

**Version**: 1.0.0 | **Ratified**: 2026-05-08 | **Last Amended**: 2026-05-08
