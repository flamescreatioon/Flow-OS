# Sync Event Model (Superseded Reference)

This historical illustrative document is not an approved implementation design. It is superseded by the operation outbox, opaque-cursor, and domain-conflict decisions in [README.md](README.md). In particular, it must not be used to introduce a generic event store, client-clock ordering, or production sync engine before the dedicated implementation sprint.

## Overview

Event-driven synchronization using domain events for loose coupling between local writes and sync engine.

## Event Types

```typescript
// Core sync events
type SyncEventType =
  | 'entity.created'
  | 'entity.updated'
  | 'entity.deleted'
  | 'sync.started'
  | 'sync.completed'
  | 'sync.failed'
  | 'conflict.detected'
  | 'conflict.resolved';

interface SyncEvent<T = unknown> {
  id: UUID;
  type: SyncEventType;
  timestamp: ISODateTime;
  correlationId: UUID;  // Groups related events
  causationId?: UUID;   // Event that caused this
  payload: T;
  metadata: {
    source: 'local' | 'remote';
    entityType: string;
    entityId: UUID;
    userId?: UUID;
    deviceId: UUID;
  };
}
```

## Event Payloads

### Entity Created
```typescript
interface EntityCreatedPayload {
  entityType: string;
  entityId: UUID;
  data: Record<string, unknown>;  // Full entity
  isOffline: boolean;
}
```

### Entity Updated
```typescript
interface EntityUpdatedPayload {
  entityType: string;
  entityId: UUID;
  changes: Partial<Record<string, unknown>>;  // Only changed fields
  previousVersion: Record<string, unknown>;
  isOffline: boolean;
}
```

### Entity Deleted
```typescript
interface EntityDeletedPayload {
  entityType: string;
  entityId: UUID;
  isSoftDelete: boolean;
  isOffline: boolean;
}
```

### Sync Started
```typescript
interface SyncStartedPayload {
  trigger: 'manual' | 'periodic' | 'network_change' | 'app_foreground';
  pendingOperations: number;
}
```

### Sync Completed
```typescript
interface SyncCompletedPayload {
  durationMs: number;
  pushed: number;
  pulled: number;
  conflicts: number;
  failed: number;
}
```

### Conflict Detected
```typescript
interface ConflictDetectedPayload {
  entityType: string;
  entityId: UUID;
  localVersion: Record<string, unknown>;
  remoteVersion: Record<string, unknown>;
  baseVersion: Record<string, unknown>;
  conflictedFields: string[];
}
```

## Event Bus

### Web (TypeScript)
```typescript
// lib/events/event-bus.ts
type EventHandler<T> = (event: SyncEvent<T>) => Promise<void>;

class SyncEventBus {
  private handlers: Map<SyncEventType, EventHandler<unknown>[]> = new Map();

  subscribe<T>(type: SyncEventType, handler: EventHandler<T>): () => void {
    const handlers = this.handlers.get(type) || [];
    handlers.push(handler as EventHandler<unknown>);
    this.handlers.set(type, handlers);

    return () => this.unsubscribe(type, handler);
  }

  unsubscribe<T>(type: SyncEventType, handler: EventHandler<T>): void {
    const handlers = this.handlers.get(type) || [];
    const index = handlers.indexOf(handler as EventHandler<unknown>);
    if (index > -1) handlers.splice(index, 1);
  }

  async publish<T>(event: SyncEvent<T>): Promise<void> {
    const handlers = this.handlers.get(event.type) || [];
    await Promise.all(handlers.map(h => h(event as SyncEvent<unknown>)));
  }
}

// Singleton
export const syncEventBus = new SyncEventBus();
```

### Mobile (Dart)
```dart
// lib/core/events/sync_event_bus.dart
import 'package:stream_channel/stream_channel.dart';

class SyncEvent<T> {
  final String id;
  final SyncEventType type;
  final DateTime timestamp;
  final String correlationId;
  final String? causationId;
  final T payload;
  final SyncEventMetadata metadata;

  SyncEvent({
    required this.id,
    required this.type,
    required this.timestamp,
    required this.correlationId,
    this.causationId,
    required this.payload,
    required this.metadata,
  });
}

class SyncEventBus {
  final Map<SyncEventType, List<Function>> _handlers = {};
  final _controller = StreamController<SyncEvent<dynamic>>.broadcast();

  Stream<SyncEvent<dynamic>> get stream => _controller.stream;

  void subscribe<T>(SyncEventType type, Future<void> Function(SyncEvent<T>) handler) {
    _handlers.putIfAbsent(type, () => []).add(handler);
  }

  void unsubscribe<T>(SyncEventType type, Future<void> Function(SyncEvent<T>) handler) {
    _handlers[type]?.remove(handler);
  }

  Future<void> publish<T>(SyncEvent<T> event) async {
    _controller.add(event);
    final handlers = _handlers[event.type] ?? [];
    await Future.wait(handlers.map((h) => h(event)));
  }
}

final syncEventBus = SyncEventBus();
```

### Desktop (C#)
```csharp
// src/FlowOS.Sync/Events/SyncEventBus.cs

public interface ISyncEventBus {
    Task PublishAsync<T>(SyncEvent<T> @event, CancellationToken ct = default);
    IDisposable Subscribe<T>(SyncEventType type, Func<SyncEvent<T>, Task> handler);
}

public sealed class SyncEventBus : ISyncEventBus {
    private readonly ConcurrentDictionary<SyncEventType, List<Delegate>> _handlers = new();
    private readonly ILogger<SyncEventBus> _logger;

    public SyncEventBus(ILogger<SyncEventBus> logger) {
        _logger = logger;
    }

    public Task PublishAsync<T>(SyncEvent<T> @event, CancellationToken ct = default) {
        if (_handlers.TryGetValue(@event.Type, out var handlers)) {
            var tasks = handlers
                .Cast<Func<SyncEvent<T>, Task>>()
                .Select(h => h(@event));
            return Task.WhenAll(tasks);
        }
        return Task.CompletedTask;
    }

    public IDisposable Subscribe<T>(SyncEventType type, Func<SyncEvent<T>, Task> handler) {
        _handlers.AddOrUpdate(type,
            _ => new List<Delegate> { handler },
            (_, list) => { list.Add(handler); return list; });

        return new Subscription(() => {
            if (_handlers.TryGetValue(type, out var list)) {
                list.Remove(handler);
            }
        });
    }

    private sealed class Subscription : IDisposable {
        private readonly Action _dispose;
        public Subscription(Action dispose) => _dispose = dispose;
        public void Dispose() => _dispose();
    }
}

public record SyncEvent<T>(
    string Id,
    SyncEventType Type,
    DateTime Timestamp,
    string CorrelationId,
    string? CausationId,
    T Payload,
    SyncEventMetadata Metadata
);

public enum SyncEventType {
    EntityCreated,
    EntityUpdated,
    EntityDeleted,
    SyncStarted,
    SyncCompleted,
    SyncFailed,
    ConflictDetected,
    ConflictResolved
}

public record SyncEventMetadata(
    string Source,      // "local" or "remote"
    string EntityType,
    string EntityId,
    string? UserId,
    string DeviceId
);
```

## Event Flow

### Write Path
```typescript
// 1. Application writes entity
async function createProduct(product: Product) {
  // Save to local DB
  await db.products.put(product);

  // Emit event
  await syncEventBus.publish({
    id: uuidv7(),
    type: 'entity.created',
    timestamp: new Date().toISOString(),
    correlationId: uuidv7(),
    payload: {
      entityType: 'products',
      entityId: product.id,
      data: product,
      isOffline: !navigator.onLine,
    },
    metadata: {
      source: 'local',
      entityType: 'products',
      entityId: product.id,
      deviceId: getDeviceId(),
    },
  });
}

// 2. Sync Engine listens
syncEventBus.subscribe('entity.created', async (event) => {
  if (event.metadata.source === 'local') {
    await syncQueue.enqueue({
      entityType: event.payload.entityType,
      entityId: event.payload.entityId,
      operation: 'CREATE',
      payload: event.payload.data,
    });
  }
});
```

### Sync Path
```typescript
// 3. Sync Engine processes queue
async function processSyncQueue() {
  await syncEventBus.publish({
    id: uuidv7(),
    type: 'sync.started',
    timestamp: new Date().toISOString(),
    correlationId: uuidv7(),
    payload: {
      trigger: 'periodic',
      pendingOperations: await getPendingCount(),
    },
    metadata: { source: 'local', entityType: '', entityId: '', deviceId: getDeviceId() },
  });

  // ... process operations ...

  await syncEventBus.publish({
    id: uuidv7(),
    type: 'sync.completed',
    timestamp: new Date().toISOString(),
    correlationId: correlationId,  // Same as started
    payload: {
      durationMs: Date.now() - startTime,
      pushed: pushedCount,
      pulled: pulledCount,
      conflicts: conflictCount,
      failed: failedCount,
    },
    metadata: { source: 'local', entityType: '', entityId: '', deviceId: getDeviceId() },
  });
}
```

## Event Sourcing (Future)

### Event Store
```sql
CREATE TABLE sync_events (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    type VARCHAR(50) NOT NULL,
    correlation_id UUID NOT NULL,
    causation_id UUID,
    payload JSONB NOT NULL,
    metadata JSONB NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_sync_events_correlation ON sync_events(correlation_id);
CREATE INDEX idx_sync_events_type ON sync_events(type);
CREATE INDEX idx_sync_events_created ON sync_events(created_at);
```

### Projections
```typescript
// Rebuild sync status from events
async function getSyncStatusFromEvents(): Promise<SyncStatus> {
  const events = await db.syncEvents
    .where('type')
    .anyOf(['sync.started', 'sync.completed', 'sync.failed'])
    .reverse()
    .limit(100)
    .toArray();

  // Compute status from event log
  return computeStatus(events);
}
```

## Correlation & Causation

```
User creates product
    │
    ├─► entity.created (correlationId: A, causationId: null)
    │       │
    │       ├─► sync.enqueued (correlationId: A, causationId: entity.created.id)
    │       │
    │       └─► ui.updated (correlationId: A, causationId: entity.created.id)
    │
    └─► sync.started (correlationId: B, causationId: null)
            │
            ├─► sync.pushed (correlationId: B, causationId: sync.started.id)
            │       │
            │       └─► entity.synced (correlationId: B, causationId: sync.pushed.id)
            │
            └─► sync.completed (correlationId: B, causationId: sync.started.id)
```

## Testing Events

```typescript
test('entity.created event enqueues sync operation', async () => {
  // Arrange
  const enqueueSpy = vi.spyOn(syncQueue, 'enqueue');
  const product = createProduct();

  // Act
  await createProduct(product);

  // Assert
  await waitFor(() => {
    expect(enqueueSpy).toHaveBeenCalledWith({
      entityType: 'products',
      entityId: product.id,
      operation: 'CREATE',
      payload: product,
    });
  });
});
```

## Monitoring Events

### Structured Logging
```typescript
// Emit to logging system
syncEventBus.subscribe('*', async (event) => {
  logger.info('sync_event', {
    eventId: event.id,
    type: event.type,
    correlationId: event.correlationId,
    entityType: event.metadata.entityType,
    entityId: event.metadata.entityId,
    duration: event.type === 'sync.completed' ? event.payload.durationMs : undefined,
  });
});
```

### Metrics
```typescript
// Prometheus metrics
const syncEventsTotal = new Counter({
  name: 'sync_events_total',
  help: 'Total sync events',
  labelNames: ['type', 'status'],
});

syncEventBus.subscribe('sync.completed', (event) => {
  syncEventsTotal.inc({ type: 'sync.completed', status: 'success' });
});

syncEventBus.subscribe('sync.failed', (event) => {
  syncEventsTotal.inc({ type: 'sync.failed', status: 'error' });
});

syncEventBus.subscribe('conflict.detected', (event) => {
  syncEventsTotal.inc({ type: 'conflict.detected', status: 'warning' });
});
```
