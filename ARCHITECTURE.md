# Architecture

Modular monolith. Do not split into microservices until a module has an independent scale or ownership reason.

## Runtime shape

```
frontend/          React + Vite SPA (staff console)
backend/           Laravel 13 API
  app/Core         Shared HTTP, RBAC catalogs
  app/Modules/*    Business modules (routes, controllers, services, policies)
PostgreSQL         System of record
Redis              Cache + queues (Predis on Windows; ext-redis in Docker)
```

## Why this split

A separate SPA keeps the API reusable for a future patient portal and mobile apps. Sanctum personal access tokens are used for API auth so the same login path can serve the staff UI and later clients. Cookie-based SPA auth can be added later without changing the permission model.

## Module convention

Each module under `backend/app/Modules/{Name}` may contain:

- `Http/Controllers`
- `Http/Requests`
- `Http/Resources`
- `Services`
- `Policies`
- `Models`
- `Routes/api.php`

`ModuleServiceProvider` loads every `Routes/api.php` at ` /api/v1`.

Controllers stay thin. Authorization lives in policies and permission names. Mutations that matter clinically or financially must write an audit log from a service, not from the UI.

## Multi-hospital

`hospitals` and `branches` exist from Phase 1. Staff users (except `SUPER_ADMIN`) belong to a hospital. Domain queries for later modules must filter by `hospital_id`. Spatie teams are **not** enabled yet; hospital scoping is application-level so Super Admin and tenant data cannot be mixed by accident.

## RBAC

Roles and permissions are seeded from `App\Core\Support\Roles` and `PermissionCatalog`. Permission names are stable for modules that are not built yet (`patient.view`, `lab.approve`, `billing.refund`, …). A role without a permission cannot call that API.

`SUPER_ADMIN` bypasses gates via `Gate::before`. That is intentional and must stay limited to this role.

## Audit

`audit_logs` is append-only. There is no delete/update API. Passwords and tokens are redacted before storage.

## Future adapters

Keep integration points as interfaces when they appear (FHIR, DICOM, Paystack, SMS). Do not add unused adapter layers in Phase 1.
