# ADR 0012: Offline-First Synchronization

## Title

Adopt local-first synchronization for supported mobile and desktop workflows.

## Status

ACCEPTED

## Context

flowOS must remain usable through unreliable connectivity without treating client clocks, local tenant IDs, or retries as authoritative. PostgreSQL remains the canonical shared store; mobile and desktop already target SQLite.

## Decision

Use SQLite local stores, shared UUIDv7 record IDs, transactional local outboxes, UUIDv7 operation IDs, bounded idempotent push batches, incremental pull with opaque cursors and deterministic server ordering, optimistic record versions, tombstones, and domain-specific conflict resolution. Sync is authenticated and membership/tenant authorized. Device identity is distinct from user identity. The web app is not currently a full local-first client.

## Consequences

- Cloud-generated IDs are not required for valid offline creation.
- Full sync endpoints, change logs, device registry, idempotency store, and conflict UX require later implementation.
- Timestamp-only cursors and universal last-writer-wins are prohibited.
- Sensitive financial and stock reconciliation needs domain-specific semantics.
