# Web Observability

Client production logging is minimal. The local-storage preference helper logs a fixed error only; it never outputs the value or browser error object. The API client uses credentialed cookies and can expose returned request IDs to future support UI without creating new retry IDs.

No telemetry is sent externally. Future client error reporting requires redaction, tenant isolation, environment separation, sampling, and privacy review.
