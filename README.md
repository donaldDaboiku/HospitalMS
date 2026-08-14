# Hospital Management System

Production-oriented modular monolith for hospitals and clinics.

## Stack

- Backend: PHP 8.4, Laravel 13, Sanctum, PostgreSQL, Redis (Predis)
- Frontend: React 19, TypeScript, Vite, Material UI
- Auth: role and permission based access (Spatie)
- Tenancy prep: `hospitals` and `branches` on day one

## Current status

**Phase 2 — Patient core is complete for local run.**

Working now:

- Laravel API (`/api/v1`)
- React staff console
- Login / logout / protected routes
- Users, roles, permissions
- Audit log foundation
- Dashboard shell
- Tests for auth, authorization, users, and audit
- Patient registration, tenant-scoped MRNs, duplicate checks, search, and profile access auditing

Not built yet: appointments, clinical, lab, pharmacy, billing, IPD, insurance, patient portal.

## Local requirements

- PHP 8.4 with `pdo_pgsql`
- Composer
- Node.js 22+ and npm
- PostgreSQL 16+
- Redis

This machine can use the existing PostgreSQL 18 service and Laragon Redis. Docker Compose is also provided for PostgreSQL and Redis.

## Quick start

### 1. Database

Create databases `hospital_ms` and `hospital_ms_testing` (already created if you followed the Phase 1 bootstrap).

Copy environment files:

```bash
cp backend/.env.example backend/.env
```

Set `DB_*` to your PostgreSQL credentials. Generate the app key if needed:

```bash
cd backend
php artisan key:generate
```

### 2. Backend

```bash
cd backend
composer install
php artisan migrate
php artisan db:seed
php artisan serve
```

API: http://localhost:8000/api/v1/health

### 3. Frontend

```bash
cd frontend
npm install
npm run dev
```

UI: http://localhost:5173

The Vite dev server proxies `/api` to Laravel on port 8000.

### 4. Seeded accounts

Password (unless you override `SEED_ADMIN_PASSWORD`): `ChangeMe!Hms2026`

| Email | Role |
| --- | --- |
| superadmin@hms.local | SUPER_ADMIN |
| admin@hms.local | HOSPITAL_ADMIN |
| doctor@hms.local | DOCTOR |
| nurse@hms.local | NURSE |
| reception@hms.local | RECEPTIONIST |

Change these passwords before any real hospital data is stored.

## Tests

```bash
cd backend
php artisan test
```

## Docker

```bash
docker compose up -d
```

Postgres is published on host port **5433** by default so it does not collide with a local PostgreSQL 18 install on 5432.

## Laravel Horizon

Horizon requires `pcntl` / `posix` and is not installable on Windows PHP. Use `php artisan queue:work` locally. Horizon belongs in the Linux/Docker worker image (`docker/php/Dockerfile`).

## Documentation

- [ARCHITECTURE.md](ARCHITECTURE.md)
- [DATABASE.md](DATABASE.md)
- [API.md](API.md)
- [SECURITY.md](SECURITY.md)
- [DEPLOYMENT.md](DEPLOYMENT.md)
- [CONTRIBUTING.md](CONTRIBUTING.md)
- [CHANGELOG.md](CHANGELOG.md)
- [backend/docs/openapi.yaml](backend/docs/openapi.yaml)

## Suggested commit

`Initialize Phase 1 HMS foundation with Laravel API, React console, RBAC, and audit logging.`
