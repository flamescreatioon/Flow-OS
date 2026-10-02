# Correlation

`request_id` identifies one API request. Clients may send a UUID `X-Request-ID`; the API validates it, otherwise generates UUIDv7, returns it in the response header, and includes it in request log context.

`correlation_id` spans a workflow such as a sync cycle or payment process and is distinct from request IDs. `operation_id` is the durable offline idempotency identity and is never regenerated for retries. `device_id` is contextual only after server registration; clients must not invent it as authorization. Future traces may add `trace_id` without changing these meanings.
