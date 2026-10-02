# Database Constraints

## Constraint Types

| Constraint | Purpose | Example |
|------------|---------|---------|
| `PRIMARY KEY` | Unique row identifier | `id UUID PRIMARY KEY` |
| `NOT NULL` | Required field | `name VARCHAR(255) NOT NULL` |
| `UNIQUE` | Prevent duplicates | `UNIQUE (org_id, sku)` |
| `CHECK` | Value validation | `CHECK (price >= 0)` |
| `FOREIGN KEY` | Referential integrity | `FK to organizations` |
| `EXCLUSION` | Complex uniqueness | `EXCLUDE USING GIST` |

## NOT NULL

```sql
-- Required fields
name VARCHAR(255) NOT NULL,
email VARCHAR(255) NOT NULL,
price_in_minor_units BIGINT NOT NULL DEFAULT 0,
```

**Rule**: Required fields = `NOT NULL`. Nullable = genuinely optional.

## UNIQUE Constraints

### Global Uniqueness (Rare)
```sql
-- Email globally unique (users)
ALTER TABLE users ADD CONSTRAINT uq_users_email UNIQUE (email);
```

### Tenant-Scoped Uniqueness (Common)
```sql
-- SKU unique within organization
ALTER TABLE products ADD CONSTRAINT uq_products_org_sku
    UNIQUE (organization_id, sku);

-- Invoice number per organization
ALTER TABLE invoices ADD CONSTRAINT uq_invoices_org_number
    UNIQUE (organization_id, invoice_number);
```

## CHECK Constraints

```sql
-- Positive values
ALTER TABLE products ADD CONSTRAINT ck_price_positive
    CHECK (price_in_minor_units >= 0);

-- Quantity non-negative
ALTER TABLE inventory ADD CONSTRAINT ck_inventory_qty_nonneg
    CHECK (quantity >= 0);

-- Percentage bounds
ALTER TABLE tax_rates ADD CONSTRAINT ck_tax_rate_bounds
    CHECK (rate >= 0 AND rate <= 1);

-- Status enum
ALTER TABLE orders ADD CONSTRAINT ck_order_status
    CHECK (status IN ('draft', 'pending', 'confirmed', 'completed', 'cancelled'));

-- Date logic
ALTER TABLE subscriptions ADD CONSTRAINT ck_sub_dates
    CHECK (starts_at < ends_at);
```

## Foreign Keys

```sql
-- Standard
ALTER TABLE products
ADD CONSTRAINT fk_products_organizations
    FOREIGN KEY (organization_id)
    REFERENCES organizations(id)
    ON DELETE CASCADE;
```

### Composite FKs (Tenant-scoped)
```sql
-- Category also belongs to organization
ALTER TABLE products
ADD CONSTRAINT fk_products_categories
    FOREIGN KEY (organization_id, category_id)
    REFERENCES categories(organization_id, id)
    ON DELETE RESTRICT;
```

### Delete Behaviors

| Behavior | Use Case |
|----------|----------|
| `CASCADE` | Child meaningless without parent (order_items → orders) |
| `RESTRICT` | Parent must not be deleted if children exist (products → categories) |
| `SET NULL` | Optional relationship (branch_id on cash_registers) |
| `SET DEFAULT` | Rare, has sensible default |

## Exclusion Constraints (Advanced)

```sql
-- No overlapping time ranges for same resource
ALTER TABLE bookings
ADD CONSTRAINT no_overlapping_bookings
EXCLUDE USING GIST (
    room_id WITH =,
    daterange(starts_at, ends_at) WITH &&
);
```

## Do NOT

| Anti-pattern | Problem |
|--------------|---------|
| No constraints | Data corruption, orphans |
| CHECK with functions | Not portable, performance |
| CHECK with subqueries | Not allowed in PostgreSQL |
| UNIQUE without tenant scope | Global uniqueness = wrong for SaaS |