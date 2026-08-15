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
| patients | Tenant-scoped master patient record and unique MRN |
| patient_mrn_sequences | Transaction-safe MRN allocation per hospital |
| patient_contacts | Emergency, next-of-kin, and other contacts |
| patient_allergies | Structured allergy list |
| patient_medical_histories | Existing and resolved conditions |
| patient_identifications | Approved identifiers such as NIN |
| doctor_profiles | Specialty, license, availability for doctor users |
| doctor_schedules | Weekday availability windows |
| appointments | Scheduled visits with status workflow |
| encounters | Clinical visits (walk-in or from check-in) |
| triage_assessments | Vitals, BMI, priority per encounter |
| clinical_notes | Light SOAP-style notes |
| diagnoses | ICD-10-style diagnosis records |
| lab_tests | Hospital lab test catalog |
| lab_orders | Lab orders for a patient/encounter |
| lab_order_items | Ordered tests on a lab order |
| lab_specimens | Specimen collection events |
| lab_results | Preliminary/final lab results |
| radiology_orders | Imaging orders |
| radiology_reports | Radiology findings and impressions |
| suppliers | Medicine/supply vendors |
| products | Hospital medicine/product catalog |
| purchase_orders | Procurement purchase orders |
| purchase_order_items | Line items on a purchase order |
| stock_batches | Received stock with batch and expiry |
| stock_movements | Inventory receipts, dispenses, and adjustments |
| prescriptions | Medication prescriptions |
| prescription_items | Medicines on a prescription |
| dispenses | Dispense events against a stock batch |

All business primary keys are UUIDs.

## Conventions

- Foreign keys with `on delete` rules stated in migrations
- `created_at` / `updated_at` except audit logs (`created_at` only)
- Soft delete users; do not soft-delete audit logs
- JSON (maps to JSONB on PostgreSQL) for settings and audit payloads
- MRNs are unique within a hospital and allocated with a locked sequence row
- Patient photos are stored on the private disk (`patients.photo_path`) and served only via authenticated `GET /patients/{id}/photo`
- Contact/next-of-kin rows may map to another patient via `patient_contacts.related_patient_id` (same hospital only)

## Seed data

`php artisan db:seed` creates:

- Hospital `GH01` / Main Campus
- All roles and permissions
- Core departments
- Sample staff users

Never seed production with these passwords.
