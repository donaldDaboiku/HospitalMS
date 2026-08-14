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
| GET | /patients | yes | patient.view |
| POST | /patients/duplicates | yes | patient.create |
| POST | /patients | yes | patient.create |
| GET | /patients/{id} | yes | patient.view + hospital scope |
| PUT | /patients/{id} | yes | patient.edit + hospital scope |
| GET | /doctors | yes | appointment.view or clinical.view |
| GET | /appointments | yes | appointment.view |
| POST | /appointments | yes | appointment.create |
| GET | /appointments/{id} | yes | appointment.view + hospital scope |
| PUT | /appointments/{id} | yes | appointment.edit + hospital scope |
| POST | /appointments/{id}/cancel | yes | appointment.cancel |
| POST | /appointments/{id}/check-in | yes | appointment.edit |
| GET | /encounters | yes | clinical.view, triage.view, or appointment.view |
| POST | /encounters | yes | clinical.create or appointment.create |
| GET | /encounters/{id} | yes | clinical/triage/appointment view + hospital scope |
| POST | /encounters/{id}/triage | yes | triage.create |
| POST | /encounters/{id}/notes | yes | clinical.create |
| POST | /encounters/{id}/diagnoses | yes | clinical.create |
| POST | /encounters/{id}/close | yes | clinical.edit |

OpenAPI: [backend/docs/openapi.yaml](backend/docs/openapi.yaml)

There is no public delete for audit logs.
