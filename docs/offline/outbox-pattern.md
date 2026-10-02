# Outbox Pattern

Each offline-capable local mutation commits with its outbox entry in the same SQLite transaction. This prevents a changed local record with no operation available to sync.

The conceptual `sync_outbox` contains UUIDv7 `operation_id`, `organization_id`, `device_id`, entity type/id, operation, payload, base version, creation/attempt timestamps, attempt count, and status. `operation_id` identifies the mutation, not the business record. States distinguish pending, in-flight, applied, conflict, retryable failure, and terminal rejection.

Outbox processing uses bounded batches and does not delete an operation merely because a response was lost. Observability later includes pending/failed/conflict counts and last successful sync.
