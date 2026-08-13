# Deployment

## Environments

| Variable | Purpose |
| --- | --- |
| `APP_KEY` | Required |
| `APP_DEBUG` | Must be `false` in production |
| `DB_*` | PostgreSQL |
| `REDIS_*` | Cache and queues; `REDIS_CLIENT=predis` or `phpredis` |
| `FRONTEND_URL` | CORS allowlist |
| `SANCTUM_STATEFUL_DOMAINS` | SPA hosts |
| `FILESYSTEM_DISK` | `local` in dev, `s3` in production |
| `QUEUE_CONNECTION` | `redis` in production |

## Process

1. Provision PostgreSQL and Redis.
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force`
4. Seed **roles and permissions only** in production; do not use the demo `UserSeeder` as-is.
5. `php artisan config:cache && php artisan route:cache`
6. Serve the API behind HTTPS (Nginx / Laravel Herd / Laragon / container).
7. Build the frontend (`npm run build`) and serve it from the same site or a locked-down static host.
8. Run `php artisan queue:work` (or Horizon on Linux).
9. Schedule `php artisan schedule:run` every minute.

## Backups

PostgreSQL backups are mandatory before Phase 2 patient data. Store backups encrypted. Test restores.

## Windows note

Local development on this workstation uses Laragon PHP, PostgreSQL 18, and Redis. Horizon is Linux-only.
