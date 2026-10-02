# Redis Responsibilities

## Fundamental Rule

```
PostgreSQL = authoritative durable business data
Redis      = temporary infrastructure state
```

## Approved Redis Responsibilities

Redis may eventually support:

| Use Case | Durability Requirement | Failure Behavior |
|----------|------------------------|------------------|
| **Cache** | Low — recreatable from PostgreSQL | Bypass to DB on miss/unavailable |
| **Queue Transport** | Medium — jobs persisted in DB first | Preserve DB state, surface enqueue failure |
| **Web Sessions** | Medium — user-facing but recoverable | Session auth fails, public routes unaffected |
| **Rate Limiting** | Low — temporary counters | Fail-open or fallback to local |
| **Distributed Locks** | Low — short-lived coordination | Expiry-based release, verify ownership |
| **Temporary Coordination** | Low — ephemeral state | Graceful degradation |

## Prohibited Redis Responsibilities

Do NOT store authoritative business data only in Redis:

- Sales transactions
- Payments
- Inventory levels
- Invoices
- Journal entries
- Memberships
- Accounting records
- Financial balances
- Idempotency keys for financial operations (use PostgreSQL)

## Cache Is an Optimization

The application must not rely on cache presence for business correctness unless explicitly designed as temporary infrastructure state.

Preferred principle:
```
cache miss
    ↓
load authoritative data
    ↓
optionally cache
```

A missing cache entry is not data loss.

## Data Recreatability

All Redis data must be:
- Recreatable from PostgreSQL, or
- Safely recoverable according to its use case, or
- Acceptable to lose (rate limit counters, ephemeral locks)