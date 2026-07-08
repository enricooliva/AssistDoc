# Quickstart: Administrative User Search and Soft Delete

## Backend verification

1. Run the enterprise-user search and delete tests:

```bash
cd backend
php artisan test --filter="EnterpriseUserManagementSearchTest|EnterpriseUserDeleteTest|AuthorizationTest|TenantIsolationTest"
```

2. Run route coverage for the updated administrative contracts:

```bash
cd backend
php artisan test --filter="ApiRoutesTest|CurrentUserTest"
```

## Frontend verification

1. Run the Angular unit tests for the user-management area:

```bash
cd frontend
npm test -- --include src/app/features/users/**/*.spec.ts
```

2. Run the Playwright enterprise-user lifecycle suite once search and delete
coverage are added:

```bash
cd frontend
npm run e2e -- enterprise-user-lifecycle
```

## Manual smoke test

1. Sign in as a `super-admin`.
2. Open the existing `/users` administration area.
3. Confirm the user list still loads with tenant-scoped pagination.
4. Search by a known full name and confirm only matching tenant-scoped users
   are returned.
5. Search by a known email and confirm the same behavior.
6. Clear the search and confirm the standard list is restored.
7. Search for a non-existing user and confirm the empty state is shown in
   Italian.
8. Soft delete a listed user and confirm the UI asks for explicit
   confirmation.
9. Confirm the deleted user disappears from the active list and from standard
   search results immediately after success.
10. Repeat the same delete request and confirm a clear conflict-style outcome
    is shown.
11. Confirm unauthorized roles cannot search or delete through the user
    administration endpoints.
12. Review audit history and confirm the delete action records actor, tenant,
    target user, timestamp, and outcome.
