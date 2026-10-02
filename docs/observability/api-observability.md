# API Observability

`RequestContextMiddleware` validates or generates `X-Request-ID`, returns it, records monotonic request duration, provides safe `flowos-api` log context, and clears log context in `finally`. It does not perform tenant authorization.

Tenant middleware adds organization context only after active-membership validation, records safe tenant events, and keeps its existing `finally` cleanup. Request logs avoid headers, query strings, bodies, cookies, and tokens. Laravel provides a JSON stderr channel for production configuration; no external service is required.
