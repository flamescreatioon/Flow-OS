# Mobile Observability

Dio diagnostic output is gated by `kDebugMode` and contains method, path, and status only. It does not emit headers, bodies, cookies, passwords, or Bearer tokens. Sync diagnostics omit arbitrary exception text.

Future sync lifecycle logs may record safe counts, duration, result category, retry/backoff, and correlation/session ID. They must never dump outbox payloads or customer records. Network failures should distinguish expected offline state, DNS/network, timeout, TLS, auth, conflict, validation, rate-limit, and server failures.
