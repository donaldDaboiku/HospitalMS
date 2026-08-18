# Changelog

## 0.6.0 — 2026-08-18

### Added

- Invoice creation, issuance, and payment recording with status workflow (draft → issued → partial → paid)
- Insurance providers, plans, patient insurance policies, and claim submission
- Live dashboard counts for today's revenue and outstanding bills
- Seeded accountant and cashier users
- Staff console billing page with invoice creation, issue, and payment dialog
- Accountant role now includes billing.create, payment.create, and insurance.create

## 0.5.0 — 2026-08-15

### Added

- Medicine/product catalog with stock batches, expiry-aware dispensing, and stock movements
- Prescription create/list and pharmacist dispense workflow
- Live dashboard counts for pending prescriptions and low-stock products
- Seeded pharmacist, store manager, and sample medicines
- Staff console pharmacy page for prescribe and dispense

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
