# ADR 0016: Redis Infrastructure

## Title

Establish Redis as temporary infrastructure foundation for cache, queue, sessions, and coordination.

## Status

ACCEPTED

## Context

flowOS requires a Redis foundation for:
- Production caching (reference data, expensive reads, dashboard computations)
- Queue transport for asynchronous job processing
- Web session storage (browser SPA authentication)
- Rate limiting
- Distributed locks for short-lived coordination

Previous sprints revealed that incomplete Redis configuration caused application boot failures. This ADR establishes the correct foundation.

## Decision

1. **Redis is temporary infrastructure, not business source of truth**
   - PostgreSQL remains the authoritative durable data store
   - Redis data must be recreatable or safely recoverable
   - No sales, payments, inventory, invoices, journals, memberships, or accounting records stored only in Redis

2. **Redis preferred for production cache/queue/sessions**
   - Cache: Redis backend with tenant-aware key namespacing
   - Queue: Redis transport with `sync` for local development
   - Sessions: Redis for web sessions; mobile/desktop use Sanctum tokens

3. **Tenant-aware cache key isolation mandatory**
   - Pattern: `flowos:{env}:cache:org:{org_id}:{resource}:{key}`
   - Global keys explicitly distinguished: `flowos:{env}:cache:global:{resource}:{key}`
   - Branch-scoped: `flowos:{env}:cache:org:{org_id}:branch:{branch_id}:{resource}:{key}`

4. **Environment namespacing required**
   - `flowos:local:`, `flowos:test:`, `flowos:staging:`, `flowos:production:`

5. **Health endpoint independent from Redis**
   - `GET /api/v1/health` returns 200 without Redis
   - No Redis checks in basic process health

6. **Explicit worker tenant context**
   - Queue jobs carry explicit `organization_id` (and `branch_id`, `actor_id` where needed)
   - Workers establish and clear tenant context per job
   - No ambient HTTP tenant state in workers

7. **Namespaces over Redis logical databases for isolation**
   - Do not architect around `DB 0 cache`, `DB 1 session`, `DB 2 queue`
   - Redis Cluster and managed Redis often use single database
   - Use key prefixes for isolation

8. **Distributed locks do not replace database integrity**
   - Locks for coordination only (report generation, leader election)
   - Never sole protection for unique invoice numbers, financial balances, idempotent payments
   - Locks have bounded expiry, verify ownership on release

## Consequences

- Cache miss = load from PostgreSQL (not data loss)
- Queue unavailable = surface enqueue failure, preserve DB state
- Session unavailable = authenticated web fails, public routes work
- Health endpoint always works
- Configuration cached correctly
- Development uses `sync` queue, `array` cache/session for zero-infrastructure tests
