# Contributing

Build one phase at a time. Do not implement later clinical modules until Phase 1 stays green.

1. Inspect existing module patterns under `backend/app/Modules`.
2. Add migrations — never hand-edit production tables.
3. Put business rules in services.
4. Add or update PHPUnit tests for the workflow.
5. Run `php artisan test` and `cd frontend && npm run build`.
6. Update `API.md` / OpenAPI when routes change.
7. Keep PRs small and hospital-safe: privacy, audit, and authorization come before convenience.

Do not commit `.env`, dumps, or real patient data.
