# Naming Conventions

## General Rules

| Object | Convention | Example |
|--------|------------|---------|
| Tables | `snake_case`, plural | `organizations`, `sale_items` |
| Columns | `snake_case` | `organization_id`, `created_at` |
| Indexes | `idx_{table}_{columns}` | `idx_products_org_sku` |
| Foreign Keys | `fk_{table}_{ref_table}` | `fk_products_organizations` |
| Unique Constraints | `uq_{table}_{columns}` | `uq_products_org_sku` |
| Check Constraints | `ck_{table}_{rule}` | `ck_products_price_positive` |
| Sequences | `{table}_{column}_seq` | `products_id_seq` |

## Tables

- Use **plural** nouns for table names: `organizations`, `products`, `sales`
- Use **snake_case** only (no PascalCase, camelCase)
- Avoid abbreviations unless universally understood (`id`, `uuid`, `sku`)

## Columns

- Use **snake_case** for all column names
- Primary key: `id` (UUIDv7)
- Foreign keys: `{referenced_table}_id` (e.g., `organization_id`)
- Boolean columns: `is_`, `has_`, `can_` prefix (e.g., `is_active`, `has_permission`)
- Timestamps: `created_at`, `updated_at`, `deleted_at`, `synced_at`

## Indexes

Format: `idx_{table}_{column(s)}`

```sql
-- Single column
CREATE INDEX idx_products_organization_id ON products(organization_id);

-- Composite (order matters - equality first, then range)
CREATE INDEX idx_sales_org_created ON sales(organization_id, created_at DESC);
```

## Foreign Keys

Format: `fk_{source_table}_{referenced_table}`

```sql
ALTER TABLE products
ADD CONSTRAINT fk_products_organizations
    FOREIGN KEY (organization_id) REFERENCES organizations(id)
    ON DELETE CASCADE;
```

## Unique Constraints

Format: `uq_{table}_{columns}`

```sql
-- Tenant-scoped uniqueness
ALTER TABLE products
ADD CONSTRAINT uq_products_org_sku
    UNIQUE (organization_id, sku);
```

## Check Constraints

Format: `ck_{table}_{rule}`

```sql
ALTER TABLE products
ADD CONSTRAINT ck_products_price_positive
    CHECK (price_in_minor_units >= 0);
```

## Sequences

Generally not needed with UUIDv7, but if used:
`{table}_{column}_seq`