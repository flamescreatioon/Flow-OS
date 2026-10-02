# Indexes

## Indexing Strategy

Indexes should support actual query patterns, not speculative ones.

### Standard Index Patterns

#### Tenant Scoping (Required on all tenant tables)
```sql
CREATE INDEX idx_products_organization_id ON products(organization_id);
```

### Composite Indexes: Tenant + Common Filter
```sql
-- Filter by org + status
CREATE INDEX idx_orders_org_status ON orders(organization_id, status);

-- Filter by org + date range
CREATE INDEX idx_sales_org_created ON sales(organization_id, created_at DESC);
```

### Composite: Tenant + Sort (Pagination)
```sql
-- Cursor-based pagination
CREATE INDEX idx_products_org_created ON products(organization_id, created_at DESC);
```

### Unique Constraints (Tenant-scoped)
```sql
-- SKU unique per organization
CREATE UNIQUE INDEX uq_products_org_sku ON products(organization_id, sku);

-- Invoice number per org
CREATE UNIQUE INDEX uq_invoices_org_number ON invoices(organization_id, invoice_number);
```

## Index Patterns by Query Type

| Query Pattern | Index |
|---------------|-------|
| `WHERE org_id = ? AND id = ?` | `(organization_id, id)` |
| `WHERE org_id = ? AND status = ?` | `(organization_id, status)` |
| `WHERE org_id = ? ORDER BY created_at DESC LIMIT 20` | `(organization_id, created_at DESC)` |
| `WHERE org_id = ? AND status = ? ORDER BY created_at` | `(organization_id, status, created_at DESC)` |
| `JOIN ON org_id + user_id` | `(organization_id, user_id)` |

## Specialized Indexes

### JSONB (GIN)
```sql
-- For JSONB settings/metadata
CREATE INDEX idx_products_settings_gin ON products USING GIN (settings);
-- Query: WHERE settings @> '{"featured": true}'
```

### Partial Indexes
```sql
-- Only active records
CREATE INDEX idx_products_active ON products(organization_id)
WHERE deleted_at IS NULL;

-- Only pending orders
CREATE INDEX idx_orders_pending ON orders(organization_id, created_at)
WHERE status = 'pending';
```

### Expression Indexes
```sql
-- Case-insensitive email
CREATE INDEX idx_users_email_lower ON users(lower(email));

-- Search on computed field
CREATE INDEX idx_orders_total ON orders((subtotal + tax + shipping));
```

## Index Naming

| Type | Pattern | Example |
|------|---------|---------|
| Standard | `idx_{table}_{columns}` | `idx_products_organization_id` |
| Unique | `uq_{table}_{columns}` | `uq_products_org_sku` |
| Partial | `idx_{table}_{condition}` | `idx_products_active` |
| GIN | `idx_{table}_{col}_gin` | `idx_products_settings_gin` |

## Do NOT Index

| Scenario | Reason |
|----------|--------|
| Low cardinality (boolean, few statuses) | Seq scan faster |
| Very small tables (< 100 rows) | Seq scan faster |
| Never queried columns | Waste of space/write overhead |
| Every column "just in case" | Write penalty, no benefit |

## Monitoring

```sql
-- Index size
SELECT pg_size_pretty(pg_relation_size('idx_products_org_created'));

-- Index usage stats
SELECT * FROM pg_stat_user_indexes WHERE relname = 'products';

-- Unused indexes
SELECT * FROM pg_stat_user_indexes WHERE idx_scan = 0;
```