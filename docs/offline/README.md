# Offline Architecture

This directory defines the future synchronization foundation; it does not describe implemented sync endpoints or a production engine.

- [Principles](offline-first-principles.md): local-first scope and cloud authority.
- [Local data model](local-data-model.md), [outbox](outbox-pattern.md), [device identity](device-identity.md).
- [Protocol](sync-protocol.md), [push/pull](synchronization-strategy.md), [cursors](sync-cursors.md), [idempotency](idempotency.md).
- [Versions/conflicts](conflict-resolution.md), [deletion](deletion-and-tombstones.md), [recovery](retry-and-recovery.md).
- [Authorization and security](offline-authorization.md), [sync security](sync-security.md).
- [Current audit](current-offline-audit.md).
