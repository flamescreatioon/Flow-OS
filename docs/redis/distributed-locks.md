# Distributed Locks

## Overview

Redis locks may help coordinate short-lived distributed operations. They are coordination tools, NOT replacements for database constraints.

## Approved Use Cases (Future)

- Prevent duplicate expensive report generation
- Single background processing owner (leader election)
- Short-lived coordination between workers
- Preventing concurrent cache stampede rebuilds

## Prohibited Use Cases

NEVER use Redis locking as the only protection for invariants such as:
- Unique invoice numbers
- Only one organization owner
- Financial balance integrity
- Idempotent payment processing

Use database constraints/transactions where applicable.

## Lock Expiry

Future locks MUST have bounded expiry.

Do NOT create indefinite locks that can deadlock the platform after worker failure.

Recommended: TTL of 30-300 seconds depending on operation, with automatic renewal for long operations.

## Lock Ownership

Future lock release MUST verify ownership where supported.

Do NOT implement naive:
```php
// UNSAFE
Redis::set('lock', 'value');
// ... work ...
Redis::del('lock');
```

Without safe ownership semantics. Use Laravel's cache lock abstraction:
```php
$lock = Cache::lock('report-generation:'.$orgId, 60);
if ($lock->get()) {
    try {
        // ... work ...
    } finally {
        $lock->release();
    }
}
```

## Lock Naming

```
flowos:{environment}:lock:{resource}:{key}
```

Examples:
```
flowos:production:lock:report:sales:019abc...
flowos:local:lock:worker:payment-processor
```

## Failure Behavior

If lock acquisition fails:
- For coordination: retry with jitter, then give up gracefully
- For idempotency protection: return conflict error to caller
- For leader election: accept follower role

Document per-use-case behavior.