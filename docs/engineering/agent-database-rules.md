# Agent Database Rules

Rules for database interactions, migrations, and schema changes.

---

## Before Creating Migrations

1. **Inspect existing schema** - Check current tables, columns, indexes, foreign keys
2. **Verify naming conventions** - Tables: `snake_case`, plural; Columns: `snake_case`
3. **Determine tenant ownership** - Every business table needs `organization_id`
4. **Consider indexing** - Add indexes for foreign keys and common query patterns
5. **Consider foreign keys** - Use explicit FK constraints for referential integrity
6. **Consider offline synchronization** - UUIDv7 for global uniqueness, `synced_at` timestamps
7. **Consider backward compatibility** - Never drop columns in use; prefer additive changes

---

## Migration Requirements

### Mandatory
- Every migration must have a working `down()` method
- Test rollback locally: `php artisan migrate:rollback --step=1`
- Use `uuid` primary keys with `uuid_generate_v7()` default
- Include `organization_id` on all tenant-owned tables with FK to `organizations`
- Add `created_at`, `updated_at` timestamps
- Soft deletes (`deleted_at`) for entities that may be recovered

### Naming Conventions
See [docs/database/naming-conventions.md](../database/naming-conventions.md)

```sql
-- Tables: snake_case, plural
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL,
    price_in_minor_units BIGINT NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ
);

-- Indexes: idx_{table}_{columns}
CREATE INDEX idx_products_org_sku ON products(organization_id, sku);

-- Foreign keys: fk_{table}_{ref_table}
ALTER TABLE products ADD CONSTRAINT fk_products_organizations
    FOREIGN KEY (organization_id) REFERENCES organizations(id);
```

### Offline Sync Support
Every syncable entity table must include:
```sql
synced_at TIMESTAMPTZ  -- Last successful sync timestamp
```

Sync queue table (for offline operations):
```sql
CREATE TABLE sync_queue (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    entity_type VARCHAR(100) NOT NULL,
    entity_id UUID NOT NULL,
    operation VARCHAR(10) NOT NULL,  -- 'CREATE', 'UPDATE', 'DELETE'
    payload JSONB NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

---

## Prohibited Actions

Agents must never:
- Silently drop production columns
- Destructively rewrite migrations already applied in any environment
- Remove data without explicit instruction
- Create migrations without `down()` methods
- Use auto-increment integers for business entity IDs
- Use floating-point types for money (`DECIMAL`/`NUMERIC` only)
- Create tables without `organization_id` for tenant-owned data

---

## Multi-Tenancy Enforcement

### Laravel Models
```php
// Every tenant model must use global scope
protected static function booted(): void {
    static::addGlobalScope('organization', function (Builder $builder) {
        $orgId = app(Multitenancy::class)->getTenant()?->id;
        if ($orgId) {
            $builder->where('organization_id', $orgId);
        }
    });
}
```

### Raw SQL
Always include `organization_id` in WHERE clauses:
```sql
-- Good
SELECT * FROM products WHERE organization_id = $1 AND id = $2;

-- Bad - missing tenant scope
SELECT * FROM products WHERE id = $1;
```

---

## Money Storage

- **Never** use `FLOAT`, `DOUBLE`, `REAL`, or `NUMERIC` without precision/scale
- Use exact representations: BIGINT minor units or field-specific NUMERIC(p,s)/DECIMAL(p,s).
  No universal NUMERIC(19,4) rule. See `docs/database/money.md` and `docs/finance/README.md`.

```sql
-- Correct
price_in_minor_units BIGINT NOT NULL,  -- 150000 = ₦1,500.00
currency CHAR(3) NOT NULL DEFAULT 'NGN',

-- Or for accounting precision (field-specific precision/scale)
amount NUMERIC(19,4) NOT NULL,
currency CHAR(3) NOT NULL DEFAULT 'NGN',
```

---

## Verification Checklist

Before submitting database changes:
- [ ] Migration has working `down()` method
- [ ] Tested `php artisan migrate:rollback --step=1`
- [ ] UUIDv7 primary keys used
- [ ] `organization_id` on tenant tables with FK
- [ ] `created_at`/`updated_at`/`deleted_at` timestamps
- [ ] `synced_at` column for syncable entities
- [ ] Appropriate indexes created
- [ ] No floating-point for money
- [ ] Naming conventions followed
- [ ] Backward compatible (no breaking changes)

---

## Reference Documentation

For complete database standards, see [docs/database/README.md](../database/README.md):

- [Naming Conventions](../database/naming-conventions.md)
- [Identifiers (UUIDv7)](../database/identifiers.md)
- [Timestamps](../database/timestamps.md)
- [Money Storage](../database/money.md)
- [Tenancy](../database/tenancy.md)
- [Migrations](../database/migrations.md)
- [Indexes](../database/indexes.md)
- [Foreign Keys](../database/foreign-keys.md)
- [Soft Deletes](../database/soft-deletes.md)
- [Audit Fields](../database/audit-fields.md)
- [Constraints](../database/constraints.md)
- [Development Database](../database/development-database.md)
- [Current Schema Audit](../database/current-schema-audit.md)