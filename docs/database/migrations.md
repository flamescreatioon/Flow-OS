# Database Migrations

## Migration Rules

### Mandatory Requirements

1. **Inspect existing schema first** - Check current tables, columns, indexes, FKs
2. **Prefer additive changes** - Add columns/tables, avoid destructive changes
3. **Reversible migrations** - Every migration must have working `down()` method
10. Test rollback: `php artisan migrate:rollback --step=1`
2. **No silent data loss** - Backfill before `NOT NULL`, archive before drop
4. **Separate schema from data** - Schema migrations vs data migrations
5. **Backfill before constraints** - Add column → backfill → add NOT NULL
6. **Separate risky operations** - Split large data migrations from schema changes
7. **Document irreversible changes** - Clearly mark in migration comments

### Prohibited
- Silent column drops
- Destructive rewrites of applied migrations
- Data removal without explicit instruction
- Migrations without `down()` method
- Auto-increment IDs for business entities
- Floating-point for money (use integer minor units or a field-specific `DECIMAL`/`NUMERIC` representation)

## Naming Conventions

### Migration Files
```bash
# Format: YYYY_MM_DD_HHMMSS_action_table
2024_01_15_103000_create_organizations_table
2024_01_16_142000_add_status_to_invoices_table
2024_01_17_090000_add_organization_id_to_products_table
```

### Migration Class
```php
class CreateProductsTable extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v7()'));
            $table->foreignUuid('organization_id')
                ->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('sku', 100);
            $table->bigInteger('price_in_minor_units')->default(0);
            $table->char('currency', 3)->default('NGN');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['organization_id', 'sku']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
}
```

## Migration Structure

### Standard Template
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v7()'));

            // Tenant ownership
            $table->foreignUuid('organization_id')
                ->constrained('organizations')->cascadeOnDelete();

            // Business columns
            $table->string('name', 255);

            // Money (integer minor units)
            $table->bigInteger('price_in_minor_units')->default(0);
            $table->char('currency', 3)->default('NGN');

            // Timestamps
            $table->timestampsTz();
            $table->softDeletesTz();

            // Sync metadata (for offline-capable entities)
            $table->timestampTz('synced_at')->nullable();

            // Indexes
            $table->unique(['organization_id', 'sku']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

### Reversible Changes

| Operation | Up | Down |
|-----------|-----|------|
| Add column | `$table->string('col')` | `$table->dropColumn('col')` |
| Add FK | `$table->foreignUuid(...)->constrained()` | `$table->dropForeign([...])` |
| Add index | `$table->index(...)` | `$table->dropIndex(...)` |
| Rename column | `$table->renameColumn('old', 'new')` | `$table->renameColumn('new', 'old')` |
| Change type | `$table->text('col')->change()` | `$table->string('col', 255)->change()` |

### Data Migrations (Separate Files)

```php
// 2024_01_15_103001_populate_default_roles.php
public function up(): void
{
    Role::insert([
        ['id' => Uuid::uuid7(), 'name' => 'owner', ...],
        ['id' => Uuid::uuid7(), 'name' => 'admin', ...],
    ]);
}

public function down(): void
{
    Role::whereIn('name', ['owner', 'admin'])->delete();
}
```

## Special Cases

### Adding NOT NULL Column to Existing Table
```php
// 1. Add nullable column
$table->string('new_col')->nullable();

// 2. Backfill data (separate migration or same)
DB::table('table')->update(['new_col' => 'default_value']);

// 3. Make NOT NULL (separate migration)
$table->string('new_col')->nullable(false)->change();
```

### Renaming Tables
```php
// Use rename, not drop+create
Schema::rename('old_name', 'new_name');

// down()
Schema::rename('new_name', 'old_name');
```

### Adding Columns to Large Tables
```php
// For large tables, add nullable first, backfill, then constrain
$table->string('new_col')->nullable(); // Fast

// Later migration: backfill
// Then constrain
$table->string('new_col')->nullable(false)->change();
```

## Testing Migrations

```bash
# Test up
php artisan migrate --path=database/migrations/2024_01_15_create_products.php

# Test rollback
php artisan migrate:rollback --step=1

# Full cycle
php artisan migrate:fresh --seed
```

## Validation Checklist

Before committing migration:
- [ ] `down()` method works (tested)
- [ ] UUIDv7 primary key
- [ ] `organization_id` with FK to organizations
- [ ] `created_at`/`updated_at` (timestamptz)
- [ ] `deleted_at` for soft deletes (if applicable)
- [ ] `synced_at` for syncable entities
- [ ] Appropriate indexes (FK, tenant scoping, common queries)
- [ ] No auto-increment IDs
- [ ] No floating-point for money
- [ ] Naming conventions followed
- [ ] Backward compatible (additive preferred)
