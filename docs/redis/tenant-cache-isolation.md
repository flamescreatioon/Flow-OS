# Tenant Cache Isolation

## Mandatory Rule

Every cache key containing tenant-owned data MUST include tenant identity.

## Key Patterns

### Organization-Scoped (Primary)

```
flowos:{environment}:cache:org:{organization_id}:{resource}:{key}
```

### Branch-Scoped

```
flowos:{environment}:cache:org:{organization_id}:branch:{branch_id}:{resource}:{key}
```

### Global (Explicit Only)

```
flowos:{environment}:cache:global:{resource}:{key}
```

Only truly global data (no tenant ownership) may use this scope.

## Prohibited Patterns

Do NOT create tenant cache keys without organization scope:
```
settings              ✗
products              ✗
dashboard             ✗
permissions           ✗
```

## Implementation Approaches

### Option 1: Explicit Key Builders (Recommended)

Create helper functions/classes that enforce tenant context:

```php
// Example helper
class TenantCacheKey
{
    public static function org(string $orgId, string $resource, string $key): string
    {
        $env = config('app.env') ?? 'local';
        return "flowos:{$env}:cache:org:{$orgId}:{$resource}:{$key}";
    }

    public static function branch(string $orgId, string $branchId, string $resource, string $key): string
    {
        $env = config('app.env') ?? 'local';
        return "flowos:{$env}:cache:org:{$orgId}:branch:{$branchId}:{$resource}:{$key}";
    }

    public static function global(string $resource, string $key): string
    {
        $env = config('app.env') ?? 'local';
        return "flowos:{$env}:cache:global:{$resource}:{$key}";
    }
}
```

Usage:
```php
$key = TenantCacheKey::org($orgId, 'settings', 'general');
Cache::put($key, $settings, now()->addHour());
```

### Option 2: Spatie Multitenancy PrefixCacheTask

The `PrefixCacheTask` was intentionally removed from `config/multitenancy.php`. Re-enabling requires:

1. Document exactly when it activates (request with active tenant)
2. Document when it clears (request end, queue worker job boundaries)
3. Document how queue workers avoid tenant leakage (explicit context establish/clear per job)
4. Document how global cache keys are handled (excluded from prefixing)

**Decision**: Prefer Option 1 (explicit key builders) for transparency and testability.

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

## Testing Tenant Cache Isolation

Tests must prove:
- Org A cache key ≠ Org B cache key
- Global key ≠ Organization key
- Branch-scoped keys include both org and branch context

No fake product caching endpoints required — test the key builder directly.