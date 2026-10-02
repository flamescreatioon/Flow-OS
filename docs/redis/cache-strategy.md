# Cache Strategy

## Overview

Redis is the preferred production cache backend. Caching is an optimization, not a requirement for business correctness.

## Approved Cache Use Cases

- Reference data (currencies, countries, timezones)
- Expensive read aggregations
- Organization settings
- Permission snapshots (with invalidation on role/membership change)
- Dashboard computations
- Short-lived API response state
- Computed lookup tables

## Do Not Cache

- Business data merely because Redis is available
- Authoritative financial records
- User-generated content without explicit invalidation strategy
- Large Eloquent models when a smaller DTO suffices

## Cache Key Naming

### Tenant-Scoped Keys (Mandatory)

```
flowos:{environment}:cache:org:{organization_id}:{resource}:{key}
```

Examples:
```
flowos:production:cache:org:019abc...:settings
flowos:local:cache:org:019xyz...:products:list
flowos:staging:cache:org:019def...:dashboard:summary
```

### Branch-Scoped Keys

```
flowos:{environment}:cache:org:{organization_id}:branch:{branch_id}:{resource}:{key}
```

### Global Keys (Explicitly Distinguished)

```
flowos:{environment}:cache:global:{resource}:{key}
```

Only truly global data may use this scope. Do not label tenant-owned data as global for convenience.

### Key Requirements

- Predictable, namespaced, lowercase where practical
- Semantically clear
- No secrets in key names
- No huge serialized identifiers or user-provided text

## Environment Isolation

Development, testing, staging, and production Redis keys MUST NOT collide.

Key prefixes distinguish environment:
```
flowos:local:
flowos:test:
flowos:staging:
flowos:production:
```

Uses existing `APP_ENV` convention.

## Cache TTL Policy

Every cache category should have a deliberate TTL. No global `forever` for arbitrary application cache.

| Category | TTL Range | Invalidation |
|----------|-----------|--------------|
| Short-lived (API responses, rate limit counters) | 30s – 5m | Natural expiry |
| Medium-lived (dashboard, permissions) | 5m – 1h | Event-driven + TTL fallback |
| Long-lived/reference (settings, lookup tables) | 1h – 24h | Event-driven invalidation |
| Explicitly invalidated | N/A | On authoritative change |

Exact TTLs remain domain-specific.

## Cache Invalidation

When authoritative data changes, relevant caches must either:
- Be invalidated (deleted)
- Be updated (recomputed and stored)
- Expire within an acceptable window

Do not design around vague "eventually cache updates itself" assumptions.

## Cache Tags

Laravel cache tags may be useful with Redis. Do not mandate universally. If used, ensure compatibility with tenant isolation and selected cache backend.

## Cache Serialization

- Do not place secrets or unnecessary sensitive data in cache
- Cached structures should be intentionally serialized
- Do not cache entire Eloquent models by default when a smaller DTO/value is sufficient
- Use `json` serialization for complex structures

## Rate Limiting (Future)

Redis is an appropriate backend for distributed rate limiting. Potential categories:
- Login attempts
- Password reset requests
- Authenticated API endpoints
- Sync operations
- High-cost reports

Do not define arbitrary production rate numbers during foundation phase.