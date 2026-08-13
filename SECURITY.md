# Security

This is a healthcare system. Treat every patient field as confidential once Phase 2 exists.

## Implemented in Phase 1

- Password hashing (bcrypt)
- Sanctum tokens with expiry
- Login rate limit (5 / minute / IP)
- Role and permission checks on staff APIs
- Hospital scoping so one hospital cannot read another hospital's users
- Inactive accounts cannot authenticate
- Security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`)
- Encrypted sessions
- CORS allowlist via `FRONTEND_URL`
- Audit log redaction for `password` and `token`
- Secrets only in `.env` (never commit `.env`)

## Explicit non-goals for Phase 1

- Field-level encryption of clinical data (no clinical tables yet)
- Patient consent records
- DICOM / FHIR security profiles

## Rules

- Do not log request bodies that may contain clinical or payment data
- Do not return SQL or stack traces when `APP_DEBUG=false`
- Super Admin is the only role that bypasses policies
- Ordinary users cannot delete audit records
- Frontend stores the API token in `sessionStorage` (XSS-sensitive). Production should move to httpOnly cookies or a BFF before internet exposure.

## Laravel Horizon

Not installed on Windows. Queue workers must run in a Linux image that has `pcntl`.
