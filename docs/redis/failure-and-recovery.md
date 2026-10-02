# Failure and Recovery

## Core Principle

Different Redis uses must fail differently. Do not treat all Redis failures identically.

## Cache Unavailable

Where safe:
```
cache bypass
    → authoritative DB
```

Rather than total application failure.

### Implementation

```php
try {
    return Cache::get($key);
} catch (RedisException $e) {
    // Log, then bypass to DB
    return $this->loadFromDatabase($key);
}
```

Fallback behavior must be deliberate. Some operations can bypass cache; others may rely on temporary state whose absence should return a controlled error.

## Queue Unavailable

Durable business mutation MUST NOT be lost.

The system should:
- Preserve authoritative DB state
- Surface enqueue failure appropriately
- Retry/repair according to future domain design

### Implementation

```php
DB::transaction(function () {
    $entity = Entity::create([...]);
    // Only dispatch after successful DB commit
    ProcessEntity::dispatch($entity->id, $orgId);
});
```

If `dispatch()` fails, the entity exists in DB. Job can be re-dispatched manually or via retry logic.

## Session Unavailable

Session-authenticated browser operations MAY fail because session storage is unavailable.

This is DIFFERENT from public process health.

### Behavior

| Route Type | Redis Session Down |
|------------|-------------------|
| Public (health, login) | Unaffected |
| Authenticated web | Fail with clear error |
| API (token-based) | Unaffected |

## Health Endpoint

**MANDATORY**: `GET /api/v1/health` MUST continue returning HTTP 200 when Redis is unavailable.

```json
{
  "status": "ok",
  "service": "flowos-api"
}
```

Do NOT add Redis checks to basic process health.

## Readiness (Future)

A future dependency-aware endpoint may check:
- PostgreSQL
- Redis

Conceptually: `/api/v1/ready`

Do NOT implement unless explicitly justified. Health and readiness are separate concepts.

## Cache Failure Handling

Do NOT swallow every Redis exception globally.

```php
// BAD - swallows all Redis errors
try {
    Cache::put($key, $value);
} catch (\Exception $e) {
    // silently ignored
}

// GOOD - deliberate fallback
try {
    Cache::put($key, $value);
} catch (RedisException $e) {
    Log::warning('Cache write failed', ['key' => $key, 'error' => $e->getMessage()]);
    // Continue without caching
}
```

## Recovery Patterns

| Failure Type | Recovery |
|--------------|----------|
| Redis restart | Automatic reconnect (Laravel handles) |
| Network partition | Circuit breaker, fallback to DB |
| Memory pressure | Eviction policy handles cache; queues/sessions need separate instances |
| Data corruption | Flush affected keys, rebuild from source |

## Observability (Future)

Metrics to track:
- Cache hit/miss ratio
- Redis latency (p50, p95, p99)
- Connection errors
- Queue depth
- Oldest queued job age
- Failed job count
- Memory usage
- Eviction rate

Do NOT implement monitoring in foundation phase (Sprint 0.23).