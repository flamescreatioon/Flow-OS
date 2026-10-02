# Offline Sync Observability

Future events include `sync.started`, `sync.push.started`, `sync.push.completed`, `sync.pull.started`, `sync.pull.completed`, `sync.conflict`, `sync.retry_scheduled`, `sync.failed`, and `sync.completed`. Safe fields include correlation ID, validated organization/device IDs, operation/conflict counts, attempt, and duration.

Long Nigerian connectivity gaps are expected conditions, not application crashes. Server `received_at` is authoritative for processing; client `occurred_at` is contextual. Sync must reauthorize server-side and preserve `operation_id` across retries.
