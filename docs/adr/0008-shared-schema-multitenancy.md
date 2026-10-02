# ADR 0008: Shared Schema Multi-Tenancy

## Title
Use Shared Schema Multi-Tenancy

## Status
SUPERSEDED

## Current Status Note

This original shared-schema decision is preserved as historical context. Its tenant-resolution priority and RLS mitigation language no longer describe the active architecture. It is superseded by [ADR 0023](0023-shared-schema-tenancy-and-authorization.md), which records shared-schema tenancy with explicit membership authorization, Sanctum, and no PostgreSQL RLS.

## Context
flowOS is a multi-tenant SaaS serving Nigerian businesses. Need tenant isolation strategy with:
- Cost efficiency (shared infrastructure)
- Operational simplicity
- Data isolation
- Scalability

## Decision
Use **shared database, shared schema** with `organization_id` column on all tenant-owned tables.

### Implementation

```sql
-- Every tenant-owned table
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    organization_id UUID NOT NULL REFERENCES organizations(id),
    name VARCHAR(255) NOT NULL,
    -- ... other columns
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Automatic filtering via global scope
CREATE INDEX idx_products_org ON products(organization_id);
```

### Laravel Implementation

```php
// Trait for automatic scoping
trait BelongsToOrganization {
    protected static function booted() {
        static::addGlobalScope('organization', function (Builder $builder) {
            $orgId = app(Multitenancy::class)->getTenant()?->id;
            if ($orgId) {
                $builder->where('organization_id', $orgId);
            }
        });
    }
}

class Product extends Model {
    use BelongsToOrganization;
}
```

### Tenant Resolution Priority
1. Subdomain: `org.flowos.local`
2. Header: `X-Organization-ID`
3. JWT Claim: `organization_id`

## Consequences

### Positive
- **Cost**: Single database, shared resources
- **Simplicity**: One schema, one migration set
- **Operations**: Single backup, monitoring, scaling
- **Cross-tenant Queries**: Easy analytics (with admin access)

### Negative
- **Noisy Neighbor**: One tenant's load affects others
- **Data Leakage Risk**: Bug in scope = data leak
- **Schema Changes**: Affect all tenants
- **Scaling**: Vertical only, no per-tenant sharding

### Mitigations
- Comprehensive automated tests for scoping
- Row-level security (PostgreSQL RLS) as defense-in-depth
- Resource quotas per tenant
- Read replicas for analytics

## Alternatives Considered

### Separate Databases
- **Pros**: Full isolation, per-tenant scaling, custom schemas
- **Cons**: High operational overhead, migration complexity, cost

### Separate Schemas
- **Pros**: Better isolation, per-tenant migrations
- **Cons**: Connection pooling complexity, migration orchestration

### Row-Level Security Only
- **Pros**: Database-enforced
- **Cons**: Complex policies, connection context management

## Future Evolution

| Trigger | Evolution |
|---------|-----------|
| >1000 tenants | Evaluate separate schemas |
| Enterprise customers | Offer dedicated database tier |
| Compliance requirements | Per-tenant encryption |

## References
- [spatie/laravel-multitenancy](https://spatie.be/docs/laravel-multitenancy)
- [PostgreSQL RLS](https://www.postgresql.org/docs/current/ddl-rowsecurity.html)
