# Redis Foundation

## Quick Reference

| Aspect | Configuration | Documentation |
|--------|--------------|---------------|
| **Cache** | Redis (production) / array (tests) | [cache-strategy.md](cache-strategy.md) |
| **Queue** | Redis (production) / sync (local, tests) | [queue-strategy.md](queue-strategy.md) |
| **Sessions** | Redis (production web) / array (tests) | [session-strategy.md](session-strategy.md) |
| **Rate Limiting** | Redis (planned) | [cache-strategy.md#rate-limiting](cache-strategy.md#rate-limiting) |
| **Distributed Locks** | Redis (planned) | [distributed-locks.md](distributed-locks.md) |

## Core Rules

- **PostgreSQL = authoritative durable business data**
- **Redis = temporary infrastructure state**
- Redis is NOT the source of truth for sales, payments, inventory, invoices, journals, memberships, or accounting records
- Health endpoint (`/api/v1/health`) MUST work without Redis
- Tenant cache keys MUST include organization context: `flowos:{env}:cache:org:{org_id}:...`
- Environment isolation via key prefixes: `flowos:local:`, `flowos:test:`, `flowos:staging:`, `flowos:production:`
- Namespaces/prefixes over Redis logical databases for isolation

## Key Files

| File | Purpose |
|------|---------|
| `apps/api/config/cache.php` | Cache stores configuration |
| `apps/api/config/database.php` | Redis connections (default, cache) |
| `apps/api/config/session.php` | Session driver configuration |
| `apps/api/config/queue.php` | Queue connections (to be created) |
| `docker-compose.yml` | Redis service definition |
| `.env.example` | Environment variables |

## Quick Commands

```bash
# Start Redis
docker compose up -d redis

# Test Redis connection
redis-cli ping

# Clear Laravel config cache
cd apps/api && php artisan config:clear

# Run tests (uses array/sync drivers, no Redis required)
cd apps/api && php artisan test
```