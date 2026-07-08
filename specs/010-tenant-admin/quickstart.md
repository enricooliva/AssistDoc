# Quickstart: Tenant Administration

## Backend verification

1. Run the tests for tenant onboarding and tenant-scoped user provisioning:

```bash
cd backend
php artisan test --filter="TenantAdministrationCreateTest|TenantUserProvisioningTest|TenantOverviewTest"
```

2. Run authorization and route coverage for the new tenant admin endpoints:

```bash
cd backend
php artisan test --filter="TenantAdministrationAuthorizationTest|ApiRoutesTest"
```

## Frontend verification

1. Run the Angular tests for the tenant administration area:

```bash
cd frontend
npm test -- --include src/app/features/tenants/**/*.spec.ts
```

2. Run the end-to-end checks for super-admin tenant onboarding:

```bash
cd frontend
npm run e2e -- tenant-admin
```

## Manual smoke test

1. Sign in as a `super-admin`.
2. Open the tenant administration area as a `super-admin`.
3. Create a tenant with a valid tenant name and initial admin details.
4. Confirm the tenant is active and visible in the tenant overview.
5. Confirm the initial admin is associated with the tenant and can access only that tenant.
6. Sign in as the created `tenant-admin` and open the user management area.
7. Add a second user to the same tenant.
8. Confirm the new user appears in the tenant membership summary.
9. Attempt to create a tenant without an initial admin and confirm the request is rejected.
10. Attempt to add a user to an inactive or missing tenant and confirm the request is rejected.
11. Review audit history and confirm tenant creation and user provisioning are recorded with actor, tenant, and outcome.
