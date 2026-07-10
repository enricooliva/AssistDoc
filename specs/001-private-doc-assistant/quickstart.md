# Quickstart: AssistDoc Private Document Assistant

## Goal

Run the MVP locally with Docker Compose, seed a development tenant, and verify the primary flows: sign-in, document upload, semantic search, cited chat, and audit logging.

## Prerequisites

- Docker and Docker Compose
- Available local ports for frontend, backend, MySQL, Qdrant, and Ollama
- Development credentials for a seeded tenant user

## Expected Local Services

- `frontend`: Angular SPA
- `backend`: Laravel API
- `worker`: Laravel queue worker for document processing
- `mysql`: relational storage
- `qdrant`: vector storage
- `ollama`: local model runtime

## Environment Setup

1. Create backend and frontend environment files from project examples.
2. Configure JWT secrets and local auth settings for development.
3. Configure MySQL connection values for backend and worker services.
4. Configure Qdrant and Ollama base URLs for backend and worker services.
5. Configure file storage path for uploaded documents.
6. Start the full stack with Docker Compose.

## Database and Seed Data

1. Run backend migrations.
2. Seed one active tenant.
3. Seed one `super-admin`, one `operator`, and one `viewer` user for that tenant.
4. Seed baseline role assignments and any required reference data.

## Manual Verification Flow

1. Sign in through the Angular UI with the seeded operator account.
2. Confirm the app loads into the main chat shell and shows tenant-scoped navigation.
3. Upload a supported document and confirm it appears with `uploaded` or `queued` status.
4. Wait for processing to reach `indexed`.
5. Run a semantic search and confirm results reference only uploaded tenant documents.
6. Ask a chat question and confirm the answer contains citations.
7. Sign in as a `viewer` and confirm upload actions are unavailable and denied server-side.
8. Sign in as a privileged user and confirm audit filters expose sign-in, upload, query, and denial events only for the same tenant.

## Test Execution Order

1. Run contract validation against the OpenAPI document.
2. Run backend auth, authorization, document workflow, search, chat, and audit API tests.
3. Run frontend unit and component tests for auth shell, document list, chat screen, and audit table.
4. Run Playwright tests for:
   - Sign-in and sign-out
   - Upload and processing status tracking
   - Search and cited chat
   - Unauthorized action denial
   - Audit-log filtering

## Implementation Notes

- Keep the chat screen visually simple: left conversation rail, central thread, fixed bottom composer, citation panel or inline expandable citations.
- Preserve Italian localization from the first implemented screen onward.
- Treat tenant isolation as a mandatory acceptance condition in every manual and automated test.
