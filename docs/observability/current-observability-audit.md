# Current Observability Audit (Sprint 0.23)

| Area | State | Current position |
|---|---|---|
| API logging | PARTIAL | Structured request and tenant events; JSON stderr channel available. |
| Request correlation | IMPLEMENTED | Validated/generated request IDs returned in API headers. |
| Tenant context | IMPLEMENTED | Validated tenant IDs only; request and log contexts clear. |
| Web logging | PARTIAL | Minimal safe console usage; no external reporting. |
| Mobile logging | PARTIAL | Debug-gated safe Dio/sync metadata only. |
| Desktop logging | PARTIAL | Microsoft.Extensions.Logging console/debug foundation. |
| Offline/sync | ARCHITECTED | Safe event model documented; prototype has limited diagnostics. |
| Audit events | ARCHITECTED | Separate append-only PostgreSQL design only. |
| Metrics | ARCHITECTED | Vendor-neutral categories/label guidance only. |
| Health | IMPLEMENTED | Public liveness, dependency-independent. |
| Readiness | NOT IMPLEMENTED | Documented future protected/internal endpoint. |
| Error tracking | NOT IMPLEMENTED | No SaaS or external shipping selected. |
| Redaction | IMPLEMENTED | Rules and tested recursive helper. |
| Retention | ARCHITECTED | Configurable policy; no values invented. |
