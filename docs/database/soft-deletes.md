# Soft Deletes

## When to Use

**Use soft deletes when:**
- Entity may need recovery (user error, audit requirement)
- Historical references must remain valid
- Soft delete is part of business workflow (archive vs delete)

**Avoid soft deletes when:**
- Entity is truly disposable (logs, temp data)
- Hard delete is explicit business requirement
- Adds unnecessary complexity for truly disposable data

## Implementation

```sql
-- Column
deleted_at TIMESTAMPTZ NULL

-- Partial index for active records
CREATE INDEX idx_products_active ON products(organization_id)
WHERE deleted_at IS NULL;
```

## Laravel

```php
// Migration
$table->softDeletesTz('deleted_at');

// Model
use SoftDeletes;

protected $dates = ['deleted_at'];
```

## Query Behavior

```php
// Default: excludes soft deleted
Product::all();           // WHERE deleted_at IS NULL
Product::withTrashed();   // Includes deleted
Product::onlyTrashed();   // Only deleted

// Restore
$product->restore();

// Force delete (permanent)
$product->forceDelete();
```

## Soft Delete + Multi-Tenancy

```sql
-- Partial index excludes deleted
CREATE INDEX idx_products_org_active ON products(organization_id)
WHERE deleted_at IS NULL;
```

## Do NOT Soft Delete

| Entity | Reason |
|--------|--------|
| Audit logs | Immutable by design |
| Financial transactions | Immutable, use reversal |
| Audit trails | Immutable by design |
| Immutable logs | Never deleted |

## Restoration

```php
// Single
$product->restore();

// Bulk
Product::onlyTrashed()->where('organization_id', $orgId)->restore();
```