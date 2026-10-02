# ADR 0007: Offline-First Architecture

## Title
Adopt Offline-First Architecture

## Status
SUPERSEDED

## Current Status Note

This original offline-first direction is preserved as historical context. Its generic conflict and sync details are superseded by [ADR 0012](0012-offline-first-synchronization.md), which defines UUIDv7 operation IDs, opaque cursors, tombstones, and domain-specific conflict handling.

## Context
flowOS targets Nigerian businesses where:
- Internet connectivity is unreliable
- Power outages are common
- Mobile data is expensive
- Business operations cannot stop

The system must work fully offline and sync when online.

## Decision
Adopt **offline-first architecture** with:
- Local database on each client (SQLite/IndexedDB)
- Optimistic UI updates
- Operation queue for sync
- Background synchronization
- Server-authoritative conflict resolution

### Data Flow
```
Local DB → Application → Sync Queue → Sync Engine → Cloud API → PostgreSQL
```

## Consequences

### Positive
- **Availability**: 100% uptime for core operations
- **Performance**: Instant local reads/writes
- **Resilience**: Works during outages
- **Cost**: Reduced data usage
- **User Experience**: No loading spinners for local actions

### Negative
- **Complexity**: Sync engine, conflict resolution, queue management
- **Storage**: Local DB size management
- **Consistency**: Eventual consistency model
- **Testing**: More scenarios to test

### Mitigations
- Well-defined sync protocols
- Comprehensive test coverage
- Monitoring and alerting
- Clear conflict resolution policies

## Alternatives Considered

### Online-First (Traditional)
- **Pros**: Simple, strong consistency
- **Cons**: Fails completely offline, poor UX in Nigeria

### Progressive Enhancement
- **Pros**: Works offline for reads, writes need online
- **Cons**: Still blocks writes offline

### Service Worker Only
- **Pros**: Caches static assets
- **Cons**: No dynamic data offline, no write support

## Sync Strategy

| Aspect | Decision |
|--------|----------|
| **Local DB** | SQLite (mobile/desktop), IndexedDB (web) |
| **Queue** | Persistent operation log |
| **Conflict Resolution** | Server wins (default), field-level merge (safe fields) |
| **Sync Trigger** | App foreground, network change, periodic (5-15min) |
| **Protocol** | REST with incremental sync endpoint |

## Conflict Resolution

Default: **Server Wins**
- Simple, predictable
- Log all conflicts for audit

Exceptions:
- **Sales/Orders**: Client wins (never lose a sale)
- **Inventory**: Operational transform for counters
- **Text Fields**: Field-level merge

## References
- [Offline First](https://offlinefirst.org/)
- [PouchDB Sync](https://pouchdb.com/guides/replication.html)
- [RxDB](https://rxdb.info/offline-first.html)
