# Database

PostgreSQL is required in development and production. Automated tests use SQLite in memory for speed; PostgreSQL-specific SQL is branched (for example `ilike` vs `like`).

## Phase 1 tables

| Table | Purpose |
| --- | --- |
| hospitals | Tenant root |
| branches | Sites under a hospital |
| users | Staff and future portal users (UUID PK, soft deletes) |
| departments | Hospital departments |
| roles / permissions / pivots | Spatie RBAC with UUID keys |
| personal_access_tokens | Sanctum API tokens (`uuidMorphs`) |
| audit_logs | Immutable activity trail |
| notifications | Laravel database notifications |
| sessions / cache / jobs | Framework |

All business primary keys are UUIDs.

## Conventions

- Foreign keys with `on delete` rules stated in migrations
- `created_at` / `updated_at` except audit logs (`created_at` only)
- Soft delete users; do not soft-delete audit logs
- JSON (maps to JSONB on PostgreSQL) for settings and audit payloads
- Unique MRN and other clinical identifiers arrive in Phase 2

## Seed data

`php artisan db:seed` creates:

- Hospital `GH01` / Main Campus
- All roles and permissions
- Core departments
- Sample staff users

Never seed production with these passwords.
