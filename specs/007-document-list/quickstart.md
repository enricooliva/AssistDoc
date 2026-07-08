# Quickstart: Document List Management

## Backend verification

1. Run the backend test suite:

```bash
cd backend
php artisan test
```

2. Focus on the document feature tests once they exist:

```bash
cd backend
php artisan test --filter Document
```

## Frontend verification

1. Run the Angular unit tests:

```bash
cd frontend
npm test
```

2. Run the Playwright end-to-end suite:

```bash
cd frontend
npm run e2e
```

## Manual smoke test

1. Sign in as `operator@assistdoc.local`.
2. Open the `Documenti` section.
3. Confirm that the document list shows pagination, tags, uploader information, and Italian loading/error/empty states.
4. Open the delete confirmation for a document and verify the UI explains that the operation is a soft delete.
5. Confirm deletion and verify that the document disappears from the normal list while remaining preserved as a soft-deleted record.
