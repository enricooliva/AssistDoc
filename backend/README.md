<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## AssistDoc Backend

This backend exposes the tenant-aware REST API used by the Angular frontend.

### Authentication endpoints

- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/logout`

### Tenant administration endpoints

- `GET /api/v1/tenants`
- `POST /api/v1/tenants`
- `GET /api/v1/tenants/{tenantId}`
- `POST /api/v1/tenants/{tenantId}/users`

### Local development users

- `admin@assistdoc.local` / `password123`
- `tenant-admin@assistdoc.local` / `password123`
- `operator@assistdoc.local` / `password123`
- `viewer@assistdoc.local` / `password123`

`admin@assistdoc.local` crea i tenant; `tenant-admin@assistdoc.local` gestisce gli utenti del proprio tenant.
- `viewer-b@assistdoc.local` / `password123`

### Tenant isolation

- every authenticated request resolves tenant context from the bearer token and the backing user record
- protected resources are filtered by `tenant_id` server-side
- role denials and invalid sessions are recorded through the audit service

### Verification

- run `php artisan route:list --path=api/v1/auth` to inspect the auth routes
- run `php artisan test tests/Feature/Auth` when a PDO SQLite driver is available
