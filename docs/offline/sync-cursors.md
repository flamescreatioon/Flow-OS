# Sync Cursors

Cursors are opaque server-issued markers of progress through a deterministic change stream. They are not `updated_at > last_sync_time`: timestamps have clock skew, ties, commit-order races, late commits, and deletion gaps. Clients store and return cursors unchanged.

When a cursor expires or its retained history is unavailable, the server returns `REBASE_REQUIRED`. The client protects unsynced outbox data, performs a controlled scoped rebootstrap, then resumes incremental pull.
