# Foreign Keys

## Convention

| Element | Convention |
|---------|------------|
| Column name | `{referenced_table}_id` (e.g., `organization_id`) |
| FK name | `fk_{source_table}_{referenced_table}` |
| Referenced column | `id` (UUID) on parent table |

## Standard Pattern

```sql
-- Column
organization_id UUID NOT NULL

-- Foreign Key
ALTER TABLE products
ADD CONSTRAINT fk_products_organizations
    FOREIGN KEY (organization_id)
    REFERENCES organizations(id)
    ON DELETE CASCADE;
```

## Laravel Migration

```php
$table->foreignUuid('organization_id')
    ->constrained('organizations')
    ->cascadeOnDelete();
```

## Delete Behavior

| Behavior | When to Use | Example |
|----------|-------------|---------|
| `CASCADE` | Child has no independent meaning | `order_items` → `orders` |
| `RESTRICT` (default) | Prevent deletion if children exist | `organizations` → `users` |
| `SET NULL` | Child can exist independently | `branch_id` on `cash_registers` |
| `SET DEFAULT` | Rare, has default | Rarely used |

### Decision Guide

| Relationship | Behavior | Rationale |
|--------------|----------|-----------|
| `order_items → orders` | CASCADE | Items meaningless without order |
| `sale_items → sales` | CASCADE | Items meaningless without sale |
| `inventory → products` | RESTRICT | Product must exist for inventory |
| `cash_registers → branches` | SET NULL | Register can exist without branch |
| `users → organizations` | RESTRICT | User must belong to org |
| `branches → organizations` | CASCADE | Branch meaningless without org |
| `payments → invoices` | RESTRICT | Payment record must remain |

### Laravel Syntax

```php
// CASCADE
$table->foreignUuid('order_id')
    ->constrained('orders')
    ->cascadeOnDelete();

// RESTRICT (default)
$table->foreignUuid('product_id')
    ->constrained('products');

// SET NULL
$table->foreignUuid('branch_id')
    ->nullable()
    ->constrained('branches')
    ->nullOnDelete();
```

## Composite Foreign Keys

For tenant-scoped relationships:

```sql
-- Product belongs to organization, belongs to category (also org-scoped)
ALTER TABLE products
ADD CONSTRAINT fk_products_categories
    FOREIGN KEY (organization_id, category_id)
    REFERENCES categories(organization_id, id)
    ON DELETE RESTRICT;
```

Laravel:
```php
$table->foreign(['organization_id', 'category_id'])
    ->references(['organization_id', 'id'])
    ->on('categories')
    ->restrictOnDelete();
```

## Naming

```sql
-- Pattern: fk_{source_table}_{target_table}
fk_products_organizations
fk_order_items_orders
fk_inventory_branches
```

Laravel auto-generates names, but explicit is preferred for clarity:
```php
$table->foreignUuid('organization_id')
    ->constrained('organizations')
    ->cascadeOnDelete()
    ->name('fk_products_organizations');
```

## Do Not

| Anti-pattern | Problem |
|--------------|---------|
| `ON DELETE CASCADE` everywhere | Accidental mass deletions |
| No FK constraints | Data corruption, orphaned records |
| `SET NULL` on required relations | Orphaned records, confusion |
| Missing FK on `organization_id` | Cross-tenant data corruption |