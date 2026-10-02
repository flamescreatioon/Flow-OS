# Multi-Tenancy Architecture

## Overview

flowOS uses a **shared database, shared schema** multi-tenancy model with `organization_id` as the tenant discriminator on all tenant-owned tables.

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

## Identity Relationship

The `Users` branch is membership-based. `users.organization_id` is retained as a legacy/default relationship while records are migrated; it is not the final authorization model. An active membership, not a selected identifier alone, authorizes tenant context.

## Tenant Hierarchy

```
Organization (Tenant)
│
├── Branches
│   └── Multiple locations per organization
│
├── Users
│   └── Staff members with roles
│
├── Devices
│   └── POS terminals, mobile devices
│
└── Business Data
    ├── Products / Inventory
    ├── Customers / Suppliers
    ├── Sales / Purchases
    ├── Invoices / Payments
    ├── Expenses / Accounting
    └── Reports / Analytics
```

## Data Isolation

### Database Level

Every tenant-owned table includes `organization_id`:

```sql
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    organization_id UUID NOT NULL REFERENCES organizations(id),
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL,
    -- ... other fields
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Automatic filtering via global scope
CREATE INDEX idx_products_org ON products(organization_id);
```

### Application Level (Laravel)

```php
// Model trait for automatic scoping
trait BelongsToOrganization {
    protected static function booted() {
        static::addGlobalScope('organization', function (Builder $builder) {
            $organizationId = app(\Spatie\Multitenancy\Multitenancy::class)->getTenant()?->id;
            if ($organizationId) {
                $builder->where('organization_id', $organizationId);
            }
        });
    }
}

// Usage
class Product extends Model {
    use BelongsToOrganization;
}

// Automatically scoped
Product::all(); // WHERE organization_id = 'current-org-id'
```

### API Level

Tenant identification priority:
1. Subdomain: `org.flowos.local` → lookup by slug
2. Header: `X-Organization-ID` → direct lookup
3. JWT claim: `organization_id` from token

```php
// Middleware resolves tenant
class TenantMiddleware {
    public function handle(Request $request, Closure $next) {
        $tenant = $this->findTenant($request);
        if ($tenant) {
            Multitenancy::makeCurrent($tenant);
        }
        return $next($request);
    }
}
```

## Queue Tenant Awareness

```php
// Jobs automatically tagged with organization_id
class ProcessSale implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $saleId) {}

    // Automatic tenant context
    public function handle() {
        // Organization scope already applied
    }
}

// Configuration
'queues' => [
    'tenant_key' => 'organization_id',
],
```

## Cache Isolation

```php
// Cache keys prefixed with tenant ID
Cache::tags(['organization:' . $orgId])->put('key', $value);

// Automatic via spatie/laravel-multitenancy
'tenant_finder' => DomainTenantFinder::class,
'switch_tenant_tasks' => [
    SwitchTenantDatabaseTask::class,
    PrefixCacheTask::class,
    SwitchRouteCacheTask::class,
],
```

## Row-Level Security (Future)

For enhanced security, consider PostgreSQL RLS:

```sql
-- Enable RLS
ALTER TABLE products ENABLE ROW LEVEL SECURITY;

-- Policy for organization isolation
CREATE POLICY products_org_isolation ON products
    USING (organization_id = current_setting('app.current_org_id')::uuid);

-- Set per connection
SET app.current_org_id = '018f4c8e-7b3d-7c8a-9f4e-1a2b3c4d5e6f';
```

## Migration Strategy

### Adding Tenancy to Existing Tables

```php
// Migration
Schema::table('existing_table', function (Blueprint $table) {
    $table->uuid('organization_id')->nullable()->after('id');
    $table->foreign('organization_id')->references('id')->on('organizations');

    // Backfill existing data
    // Then make NOT NULL
    $table->uuid('organization_id')->nullable(false)->change();
});
```

### Seeding Tenants

```php
// Database seeder
Organization::factory()->create([
    'name' => 'Demo Company',
    'slug' => 'demo',
    'timezone' => 'Africa/Lagos',
    'currency' => 'NGN',
]);
```

## Testing Multi-Tenancy

```php
// Feature test
public function test_tenant_isolation() {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    Product::factory()->for($org1)->create(['name' => 'Product A']);
    Product::factory()->for($org2)->create(['name' => 'Product B']);

    // Act as org1
    $this->actingAsTenant($org1);
    $products = Product::all();

    $this->assertCount(1, $products);
    $this->assertEquals('Product A', $products->first()->name);
}
```

## Scaling Considerations

| Approach | Pros | Cons |
|----------|------|------|
| Shared Schema (Current) | Simple, cost-effective | Noisy neighbor risk |
| Separate Schemas | Better isolation | Migration complexity |
| Separate Databases | Full isolation | High operational overhead |

**Current choice**: Shared schema with proper indexing and query optimization.

## Security Checklist

- [ ] All tenant queries scoped by `organization_id`
- [ ] Global scopes on all tenant models
- [ ] Queue jobs tenant-aware
- [ ] Cache keys prefixed
- [ ] File storage paths include org ID
- [ ] API endpoints validate tenant access
- [ ] Admin panel has tenant switcher
- [ ] Audit logs include tenant context
