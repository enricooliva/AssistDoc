# Quickstart: Admin, Tenant-Admin Access, and Full User Editing

## Backend verification

1. Run the enterprise-user lifecycle and authorization tests:

```bash
cd backend
php artisan test --filter="EnterpriseUserManagementTest|AuthorizationTest|TenantIsolationTest"
```

2. Run the API route coverage for the user area and the affected chat/document permissions:

```bash
cd backend
php artisan test --filter="ApiRoutesTest|CurrentUserTest"
```

## Frontend verification

1. Run the Angular unit tests for chat, documents, and user management:

```bash
cd frontend
npm test -- --include src/app/features/chat/**/*.spec.ts --include src/app/features/documents/**/*.spec.ts --include src/app/features/users/**/*.spec.ts
```

2. Run the end-to-end suite that covers admin access, documents, and user editing:

```bash
cd frontend
npm run e2e -- admin-tenant-user-edit
```

## Manual smoke test

1. Sign in as a `super-admin`.
2. Open chat and confirm the page loads with no authorization errors.
3. Open documents and confirm the list and upload actions render according to the current role.
4. Sign in as a `tenant-admin`.
5. Open chat and confirm the page loads and stays usable after navigation.
6. Open documents and confirm the tenant-admin can use the expected document workflow.
7. Open `/users` and confirm the user list loads for the current tenant.
8. Open an existing user record and confirm editable fields are prefilled.
9. Change the editable fields, save the form, and confirm the updated values reload in the list row.
10. Confirm read-only fields such as lock and deletion metadata are visible but not editable.
11. Try to modify a protected field through the UI and confirm the request is rejected or ignored by validation.
12. Review audit output and confirm the save action is recorded with the acting administrator and tenant context.
