# Queue Strategy

## Overview

Redis is the preferred future production queue transport unless infrastructure requirements change.

## Configuration

```env
# Production
QUEUE_CONNECTION=redis

# Local development (minimal)
QUEUE_CONNECTION=sync

# Testing
QUEUE_CONNECTION=sync
```

## Development Queue Behavior

- **Minimal local development**: `sync` driver — jobs execute inline, no worker needed
- **With Docker Redis running**: `redis` driver — enables testing queue behavior locally
- **Health endpoint**: MUST work with `sync` driver, no Redis required

Do not force developers to run a worker merely to load `/api/v1/health`.

## Queue Durability

Redis queues are NOT authoritative business records.

Critical business state MUST be persisted before dispatching asynchronous work:

```
Persist database transaction
       ↓
Dispatch job
```

Do NOT treat `queued` as equivalent to `business transaction persisted`.

## Queue Job Tenant Context

Future organization-specific jobs MUST carry explicit tenant context.

Minimum required:
```php
[
    'organization_id' => '...',
    'branch_id' => '...',        // where required
    'actor_id' => '...',         // where required
]
```

Do NOT assume ambient HTTP tenant state survives into workers.

## Queue Worker Tenant Leakage Prevention

Long-running workers MUST NOT retain organization context between jobs.

Required lifecycle:
```
receive job
    ↓
establish tenant context (from job payload)
    ↓
run authorized job
    ↓
clear tenant context
```

## Queue Authorization

A queued job should not automatically bypass authorization assumptions.

Depending on domain:
- Authorization may be checked before enqueue
- Job may revalidate current state
- Tenant ownership must always be enforced

Document the distinction per job type.

## Queue Idempotency

Future jobs MUST be safe against retries where duplicate effects are dangerous.

Redis job retry does NOT replace idempotency principles from Sprint 0.11/0.12.

Examples requiring idempotency:
- Payment processing
- Stock effects
- Notification dispatch
- Report generation

Do not implement these jobs during foundation phase.

## Failed Jobs

Current Laravel failed-job setup uses PostgreSQL for durable failure metadata.

```
Redis transports work
PostgreSQL persists failure records
```

Do NOT move failed-job history into volatile Redis merely for consistency.

## Queue Prefix

Future queue names should be environment/application scoped:

```
flowos-local-default
flowos-production-default
```

Exact naming may be configured later via Laravel's queue configuration.