# Quickstart: Text Content Ingestion

## Backend verification

1. Run the document API and processing tests affected by the new ingestion mode:

```bash
cd backend
php artisan test --filter="DocumentControllerTest|DocumentServiceTest|DocumentProcessingServiceTest|DocumentDeletionServiceTest|DocumentDeleteTest"
```

2. Run the indexing and search cleanup tests to confirm segment and vector removal remains correct:

```bash
cd backend
php artisan test --filter="DocumentIndexerServiceTest|QdrantServiceTest|SubmitChatMessageTest"
```

## Frontend verification

1. Run the Angular unit tests for the documents feature:

```bash
cd frontend
npm test -- --include src/app/features/documents/**/*.spec.ts
```

2. Run the end-to-end document flow that covers both upload and direct text ingestion:

```bash
cd frontend
npm run e2e -- text-content-ingestion
```

## Manual smoke test

1. Sign in as a user allowed to manage documents.
2. Open the documents page and confirm the create area offers both "carica file" and "incolla testo".
3. Submit a valid PDF and confirm the item appears in the list with source `file` and a processing status.
4. Submit a valid pasted text with title and tags and confirm the item appears in the list with source `text`.
5. Refresh the list after processing and confirm both items can reach `ready`.
6. Open chat or semantic retrieval and confirm both sources can be cited.
7. Submit whitespace-only text and confirm the UI blocks the request with a clear Italian validation message.
8. Upload a file with unsupported format and confirm the current file validation still applies.
9. Force a processing failure and confirm the failure reason is visible in the documents UI.
10. Retry the failed item and confirm a new preparation run starts without creating a second document row.
11. Delete a ready document and confirm its row disappears from active lists and its content no longer appears in semantic results.
