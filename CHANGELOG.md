# Changelog

## 0.4.0 — 2026-08-14

### Added

- Laboratory test catalog, orders, specimen collection, result entry, and verification
- Radiology orders and reports
- Live dashboard count for pending lab work
- Seeded lab technician and radiologist users plus sample lab tests
- Staff console pages for lab catalog/orders and radiology orders

## 0.3.0 — 2026-08-14

### Added

- Doctor profiles and weekday schedules
- Appointment booking, cancellation, and check-in (creates an open encounter)
- Encounters with triage/vitals, light clinical notes, and ICD-10-style diagnoses
- Live dashboard counts for today's appointments, waiting patients, and available doctors
- Staff console pages for today's appointments, waiting list, and encounter workflows

## 0.2.0 — 2026-08-14

### Added

- Tenant-scoped patient registration with transaction-safe, per-hospital MRNs
- Patient contacts, allergies, medical history, and approved identity records
- Duplicate-match check, search, patient profile, and audit events for record views and changes
- Staff console pages for listing, registering, and viewing patients

## 0.1.0 — 2026-08-13

### Added

- Phase 1 foundation: Laravel 13 API, React/TypeScript staff console, PostgreSQL, Redis
- Authentication, users, roles, permissions, departments
- Audit logging foundation
- Dashboard shell and sidebar navigation for later modules
- Automated tests for login, authorization, user management, and audit immutability
