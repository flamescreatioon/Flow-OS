# Mobile Architecture

The mobile client uses Flutter, Riverpod, Dio, `sqflite`, and `flutter_secure_storage`. It is local-first in direction: local SQLite working state, an outbox, a sync service, and an API client support future offline-capable modules.

## Current versus target

**Current:** the mobile sync implementation is a prototype and must not be treated as approved sync protocol. It has documented replacement work around generic CRUD assumptions, UUIDv4/timestamp behavior, and pull application.

**Target:** UUIDv7-style entity and durable operation IDs, transactional outbox writes, bounded authenticated pushes, server idempotency, opaque-cursor incremental pulls, tombstones, and domain-specific conflict handling.

Tokens belong in secure platform storage. Network and sync diagnostics must be sanitized and must not dump payloads or credentials.

See [offline sync architecture](offline-sync-architecture.md), [mobile security](../security/mobile-security.md), and [mobile observability](../observability/mobile-observability.md).
