# Synchronization Strategy

Synchronization is eventual consistency between a client SQLite store and the Laravel/PostgreSQL shared system. It uses an operation outbox for push and an incremental server change stream for pull. Full synchronization endpoints, change logs, and workers are not implemented in Sprint 0.11.

The planned cycle is: authenticate, select an authorized organization, identify/register the installation, bootstrap required data, push bounded dependency-aware batches, pull ordered changes, then report understandable state (`synced`, pending count, offline, sync error, or conflict). Network reachability is proven by successful server communication, not Wi-Fi state.

`/api/v1/sync/push` and `/api/v1/sync/pull` are reserved planned namespace paths only. Their contracts will be versioned and must return per-operation outcomes; a batch is not assumed atomic.
