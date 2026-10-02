# Observability Principles

Prefer structured, contextual, tenant-aware and correlation-aware data over verbose free-form output. Production diagnostics must remain useful without debug mode. Server timestamps are UTC instants and authoritative for server processing; client time is context only.

Logs never contain credentials, unnecessary PII, request/response bodies, or financial exports. Validated IDs may be contextual; tenant identity comes only from established server context. Logs are not business truth, audit events are distinct, and failures must remain safely diagnosable.
