# Multi-Tenancy

## Architecture

**Model**: Shared database, shared schema with `organization_id` column on all tenant-owned tables.

```sql
-- Every tenant-owned table
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    ...
);
```

## Tenant Hierarchy

```

## Identity Relationship

`users.organization_id` is the legacy/default relationship. The target is `users -> organization_memberships -> organizations`; a user may hold different roles in several organizations. The legacy column remains during the staged migration and must not be used as the final tenant-authorization model.
Organization (Tenant)
    ├── Branch
    │   └── Branch-scoped data (inventory, POS, cash registers)
    ├── Users / Memberships
    └── Business Data
        ├── Products
        ├── Customers
        ├── Sales
        ├── Inventory
        └── Accounting
```

## Tenant Entity: Organization

```sql
CREATE TABLE organizations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    timezone VARCHAR(50) NOT NULL DEFAULT 'Africa/Lagos',
    currency VARCHAR(3) NOT NULL DEFAULT 'NGN',
    settings JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

## Organization Ownership Rules

### Organization-Scoped (Most Data)
Every business record belongs to exactly one organization.

```sql
-- Required on all tenant-owned tables
organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE
```

### Branch-Scoped (Some Data)
Some data naturally belongs to a specific branch/location.

```sql
branch_id UUID REFERENCES branches(id) ON DELETE SET NULL
```

**Likely Organization-Scoped:**
- Organization settings
- Chart of accounts
- Global product definitions
- Organization-level users/roles

**Likely Branch-Scoped:**
- POS transactions
- Cash register sessions
- Branch-specific inventory levels
- Cash register sessions
- Branch-specific stock movements

## Tenant Isolation

### Application-Level (Primary)
Every query must include `organization_id`:

```sql
-- Good
SELECT * FROM products WHERE organization_id = $1 AND id = $2;

-- Bad - missing tenant scope
SELECT * FROM products WHERE id = $1;
```

### Laravel Global Scopes
```php
trait BelongsToOrganization {
    protected static function booted(): void {
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

### Database-Level (Defense in Depth)
PostgreSQL Row Level Security (RLS) as defense-in-depth:

```sql
ALTER TABLE products ENABLE ROW LEVEL SECURITY;

CREATE POLICY products_org_isolation ON products
    USING (organization_id = current_setting('app.current_org_id')::uuid);
```

## Branch Ownership

### Branch Model
```sql
CREATE TABLE branches (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(255),
    is_main BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_branches_org ON branches(organization_id);
```

### Branch Context
- User sessions can have a `branch_id` context
- POS operations default to user's branch
- Inventory can be branch-specific or organization-wide

## Tenant Resolution

### Resolution Priority
1. **Subdomain**: `org.flowos.local` → lookup by slug
2. **Header**: `X-Organization-ID` (for API clients)
3. **JWT Claim**: `organization_id` in JWT
4. **Session**: `organization_id` in session

### Middleware
```php
// Laravel middleware
class SetCurrentOrganization
{
    public function handle(Request $request, Closure $next)
    {
        $orgId = $this->resolveOrganization($request);
        app(Multitenancy::class)->setTenant($orgId);
        return $next($request);
    }
}
```

## Data Integrity

### Cross-Tenant References
```sql
-- Prevent cross-tenant references
ALTER TABLE products
ADD CONSTRAINT ck_products_org_consistency
    CHECK (organization_id = (SELECT organization_id FROM categories WHERE id = category_id));
```

### Soft Deletes & Tenancy
```sql
-- Soft deleted records stay in organization
CREATE INDEX idx_products_org_deleted ON products(organization_id, deleted_at)
WHERE deleted_at IS NULL;
```

### Tenant Switching (Admin Only)
```php
// Admin-only: impersonate tenant
Multitenancy::setTenant($orgId);
try {
    // Perform admin action in tenant context
} finally {
    Multitenancy::forgetTenant();
}
```
