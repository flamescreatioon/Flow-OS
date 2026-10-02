# Offline-First Architecture

flowOS supports selected mobile and desktop workflows through local-first SQLite stores. The path is user action → local validation → local transaction → immediate UI → outbox → Laravel API → PostgreSQL. PostgreSQL is the canonical shared state while clients may create valid UUIDv7 records offline.

The sync foundation is transactional outbox push, server-validated idempotent operations, incremental pull with opaque cursors and deterministic server ordering, versions for mutable data, tombstones, and domain-specific conflict reconciliation. Authentication, active membership, tenant isolation, and authorization remain mandatory on reconnect. Detailed decisions live in [offline documentation](../offline/README.md).
