# API

Base URL: `/api/v1`

Envelope:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": { "current_page": 1, "per_page": 15, "total": 0, "last_page": 1 }
}
```

Validation failure: HTTP 422, `success: false`, `errors` keyed by field.

Authentication: `Authorization: Bearer {token}` from `POST /auth/login`.

## Phase 1 endpoints

| Method | Path | Auth | Permission |
| --- | --- | --- | --- |
| GET | /health | no | — |
| POST | /auth/login | no | throttle: 5/min |
| GET | /auth/me | yes | active user |
| POST | /auth/logout | yes | active user |
| GET | /dashboard/summary | yes | dashboard.view |
| GET/POST | /users | yes | user.view / user.create |
| GET/PUT/DELETE | /users/{id} | yes | user.* + hospital scope |
| GET | /roles | yes | role.view |
| GET | /permissions | yes | role.view |
| GET | /audit-logs | yes | audit.view |
| GET | /audit-logs/{id} | yes | audit.view |
| GET | /departments | yes | department.view |

OpenAPI: [backend/docs/openapi.yaml](backend/docs/openapi.yaml)

There is no public delete for audit logs.
