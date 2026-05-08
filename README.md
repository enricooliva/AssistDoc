# AssistDoc

AssistDoc is a multi-tenant private document assistant for companies.

This repository contains:

- `backend/`: Laravel-style API application
- `frontend/`: Angular SPA
- `infra/`: container and deployment scaffolding
- `specs/`: feature specs, plans, tasks, and supporting design artifacts

Current implementation status:

- monorepo scaffolding created
- auth, tenant context, audit, and chat MVP skeleton in progress
- document ingestion and audit UI follow-up work remains in task phases after the MVP slice

Authentication slice status:

- `POST /api/v1/auth/login` authenticates local development users and returns a tenant-scoped bearer token
- `GET /api/v1/auth/me` restores the authenticated user context for the SPA
- `POST /api/v1/auth/logout` invalidates the current bearer token
- tenant context is derived server-side from the authenticated user, not from client input
