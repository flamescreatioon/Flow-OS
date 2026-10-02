# Current Date/Time Audit

| Area | Status | Finding |
|---|---|---|
| Database | COMPLIANT | migrations use `timestampTz`; organization default is `Africa/Lagos`. |
| Laravel | PARTIAL | timestamps are timezone-aware; no domain clock/immutable-time convention is implemented. |
| OpenAPI | CORRECT/PLANNED | existing temporal fields use `date-time`; no business date/time endpoints exist. |
| Web | NOT APPLICABLE | no temporal business logic found. |
| Mobile | AMBIGUOUS | prototype sync uses `DateTime.now()`/ISO timestamps as cursor state; superseded by opaque cursor architecture. |
| Desktop | NOT IMPLEMENTED | no temporal domain logic. |
| Offline | PARTIAL | standards prohibit client timestamp ordering; implementation is deferred. |
