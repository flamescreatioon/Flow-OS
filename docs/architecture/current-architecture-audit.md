# Current Architecture Audit (Sprint 0.24)

| Area | Status | Notes |
|---|---|---|
| Web | PARTIAL | Next.js scaffold; patched Next migration is a production blocker. |
| API | PARTIAL | Laravel foundation, health, tenancy/security/observability middleware; no business modules. |
| Mobile | PARTIAL | Local DB, token storage, and prototype sync; production sync is architected. |
| Desktop | ARCHITECTED | WPF projects and logging foundation; sync/local persistence remain placeholders. |
| PostgreSQL | IMPLEMENTED | Authoritative cloud store with Laravel migration authority. |
| Redis | IMPLEMENTED | Temporary cache/queue/session/rate-limit infrastructure. |
| Tenancy | IMPLEMENTED | Explicit subdomain/header selection plus active membership and cleanup. |
| Identity | PARTIAL | Membership model exists; roles/policies/device lifecycle remain Sprint 1+. |
| Authorization | PARTIAL | Active membership enforced; permission model is future work. |
| Offline sync | ARCHITECTED | Detailed target exists; mobile SyncEngine is a non-production prototype. |
| Contracts | PARTIAL | OpenAPI v1 is canonical; implemented surface is small. |
| Security | PARTIAL | Foundation present; Laravel/Next upgrades are production blockers. |
| Observability | PARTIAL | Request IDs/redaction/health foundation present; metrics/audit/readiness future. |
| CI | IMPLEMENTED | Cross-platform checks; Flutter analyzer is advisory hosted debt (186 findings). |
| Deployment | ARCHITECTED | Local Compose is implemented; production topology/provider unfinalized. |
| Integrations | PLANNED | Future adapter boundaries only. |

## Prototype and debt warnings

- Flutter `SyncEngine` generic CRUD/timestamp/UUIDv4 prototype is not approved sync architecture.
- Desktop sync and local persistence are placeholders.
- Device lifecycle, durable audit events, readiness, metrics, and correlation IDs are unimplemented.
- Laravel 10 and Next 14 security migrations are pre-production blockers.

## ADR follow-up for Sprint 0.25

ADR 0008/0010 historical JWT/RLS alternatives and duplicate tenancy-era material need reconciliation against accepted membership/Sanctum/shared-schema direction. This audit records the issue but does not perform the ADR review.
