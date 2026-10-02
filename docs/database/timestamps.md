# Timestamps

## Standard Columns

All business tables must include:

```sql
created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
deleted_at TIMESTAMPTZ  -- NULL = not deleted (soft delete)
```

Optional sync metadata for offline-capable entities:

```sql
synced_at TIMESTAMPTZ  -- Last successful sync to server
```

## PostgreSQL Type: `timestamptz`

**Always use `timestamptz` (timestamp with time zone).**

- Stores as UTC internally
- Converts to session timezone on output
- Preserves instant-in-time semantics
- `timestamp` (without time zone) loses timezone info

```sql
-- Correct
created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()

-- WRONG - loses timezone context
created_at TIMESTAMP NOT NULL DEFAULT NOW()
```

## Default Values

```sql
created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
```

- `created_at`: Set once on insert, never changes
- `updated_at`: Updated on every change via trigger
- `deleted_at`: NULL = active, timestamp = soft deleted

## Automatic `updated_at` Updates

Use a trigger function (defined in `02-schema.sql`):

```sql
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$;

CREATE TRIGGER update_products_updated_at
    BEFORE UPDATE ON products
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();
```

## Laravel Model Handling

```php
protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
];

// Or rely on Eloquent's automatic timestamp handling
public $timestamps = true;
```

## Timezone Handling

### Server/Database
- All `TIMESTAMPTZ` stored as **UTC** internally
- Server timezone: UTC (recommended)

### Organization Timezone
- Each organization has a `timezone` column (e.g., `Africa/Lagos`)
- Application converts UTC → org timezone for display
- Never store local time without timezone info

```php
// Organization model
protected $casts = [
    'timezone' => 'string', // e.g., 'Africa/Lagos'
];

// Display conversion
$localTime = $utcTime->setTimezone($org->timezone);
```

### Default Timezone
- Default organization timezone: `Africa/Lagos`
- Do not hardcode Lagos globally - use org's configured timezone

## Laravel Migration Timestamps

```php
$table->timestampsTz(); // created_at, updated_at as timestamptz
$table->softDeletesTz(); // deleted_at as timestamptz
```

## Do NOT Use

| Type | Reason |
|------|--------|
| `TIMESTAMP` (no TZ) | Loses timezone, ambiguous |
| `DATE` for timestamps | Loses time component |
| `BIGINT` unix timestamps | Loses timezone, not human-readable |
| `DATE` for `deleted_at` | Loses time precision |