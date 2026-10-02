# Time Testing

Business logic that depends on time should accept a controllable clock/time provider at the relevant boundary instead of scattering `now()`/`DateTime.Now`/`new Date()` through deterministic rules. Do not add a global clock abstraction before a use case needs one, and do not use sleeps to test time passage.

Future tests cover UTC conversion, `Africa/Lagos`, a DST-capable IANA timezone, business-day boundaries, expiry, and half-open ranges (`>= start`, `< end`). Test calendar dates separately from UTC instants and verify that server ordering does not trust client timestamps.
