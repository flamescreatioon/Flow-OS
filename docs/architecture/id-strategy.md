# ID Strategy

## Why Not Sequential IDs?

Sequential integer IDs (1, 2, 3...) are problematic for offline-first systems:

1. **Collision Risk**: Two offline devices create record #1001
2. **Coordination Required**: Need central ID generator
3. **No Sortability**: Can't order by creation time globally
4. **Enumeration Attack**: Easy to guess other records

## Solution: UUIDv7

**UUIDv7** (RFC 9562) provides:

```
018F4C8E-7B3D-7C8A-9F4E-1A2B3C4D5E6F
│        │        │        │        │
│        │        │        │        └── 48 bits: Random
│        │        │        └──────────── 14 bits: Random + Variant
│        │        └──────────────────── 4 bits: Version (7)
│        └──────────────────────────── 48 bits: Unix Timestamp (ms)
└──────────────────────────────────── 48 bits: Unix Timestamp (ms)
```

### Benefits

| Property | Benefit |
|----------|---------|
| **Time-ordered** | Sortable by creation time |
| **Globally unique** | No coordination needed |
| **Offline-friendly** | Generate anywhere |
| **Index-friendly** | B-tree friendly (append-only) |
| **Compact** | 16 bytes (36 chars as string) |

## Implementation

### PostgreSQL

```sql
-- Custom function for UUIDv7
CREATE OR REPLACE FUNCTION uuid_generate_v7()
RETURNS uuid LANGUAGE plpgsql AS $$
DECLARE
    ts bigint;
    uuid_bytes bytea;
BEGIN
    ts := (EXTRACT(EPOCH FROM clock_timestamp()) * 1000)::bigint;
    uuid_bytes :=
        (ts >> 40 & 0xFF)::bytea ||
        (ts >> 32 & 0xFF)::bytea ||
        (ts >> 24 & 0xFF)::bytea ||
        (ts >> 16 & 0xFF)::bytea ||
        (ts >> 8 & 0xFF)::bytea ||
        (ts & 0xFF)::bytea ||
        gen_random_bytes(10);
    uuid_bytes := set_byte(uuid_bytes, 6, (get_byte(uuid_bytes, 6) & 0x0F) | 0x70);
    uuid_bytes := set_byte(uuid_bytes, 8, (get_byte(uuid_bytes, 8) & 0x3F) | 0x80);
    RETURN uuid_bytes::uuid;
END;
$$;

-- Use as default
CREATE TABLE organizations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    ...
);
```

### Laravel (PHP)

```php
use Ramsey\Uuid\Uuid;

trait UsesUuidv7 {
    protected static function booted() {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Uuid::uuid7()->toString();
            }
        });

        $model->getKeyType() = 'string';
        $model->incrementing = false;
    }
}

class Organization extends Model {
    use UsesUuidv7;
}
```

### Flutter (Dart)

```dart
import 'package:uuid/uuid.dart';

final uuid = Uuid();

// UUIDv7 generation
String generateUuidv7() {
  final timestamp = DateTime.now().millisecondsSinceEpoch;
  final random = Uuid().v4().split('-').last; // 12 random chars
  // Proper implementation uses uuid package v4+
  return Uuid().v7(); // When available
}
```

### .NET (C#)

```csharp
using System;

public static class UuidV7 {
    public static Guid NewGuid() {
        var timestamp = DateTimeOffset.UtcNow.ToUnixTimeMilliseconds();
        var bytes = new byte[16];

        // 48-bit timestamp
        bytes[0] = (byte)(timestamp >> 40);
        bytes[1] = (byte)(timestamp >> 32);
        bytes[2] = (byte)(timestamp >> 24);
        bytes[3] = (byte)(timestamp >> 16);
        bytes[4] = (byte)(timestamp >> 8);
        bytes[5] = (byte)timestamp;

        // 74 random bits + version + variant
        RandomNumberGenerator.Fill(bytes[6..]);
        bytes[6] = (byte)((bytes[6] & 0x0F) | 0x70); // Version 7
        bytes[8] = (byte)((bytes[8] & 0x3F) | 0x80); // RFC 4122 variant

        return new Guid(bytes);
    }
}
```

### TypeScript (Web)

```typescript
// Using uuid package v9+
import { v7 as uuidv7 } from 'uuid';

const id = uuidv7();
// "018f4c8e-7b3d-7c8a-9f4e-1a2b3c4d5e6f"
```

## Migration from Sequential IDs

If migrating existing tables:

```sql
-- Add UUID column
ALTER TABLE products ADD COLUMN uuid_id UUID DEFAULT uuid_generate_v7();

-- Backfill
UPDATE products SET uuid_id = uuid_generate_v7() WHERE uuid_id IS NULL;

-- Create unique index
CREATE UNIQUE INDEX idx_products_uuid ON products(uuid_id);

-- Switch foreign keys (carefully)
-- Drop old PK, rename uuid_id to id
```

## Anti-Patterns to Avoid

| Anti-Pattern | Problem |
|--------------|---------|
| UUIDv4 (random) | Not sortable, index fragmentation |
| UUIDv1 (MAC + time) | Privacy leak (MAC address) |
| ULID | Similar but less standard |
| NanoID | Not sortable |
| Composite keys | Complex queries, ORM issues |

## Database Indexing

```sql
-- UUIDv7 works well with B-tree indexes
-- New records append to end (no page splits)
CREATE INDEX idx_products_org_created ON products(organization_id, created_at DESC);

-- For time-range queries
CREATE INDEX idx_sales_created ON sales(created_at DESC);
```

## Testing UUIDv7

```php
// Verify time ordering
$id1 = Uuid::uuid7()->toString();
usleep(1000); // 1ms
$id2 = Uuid::uuid7()->toString();

assert($id1 < $id2); // Lexicographic comparison works!
```

## References

- [RFC 9562 - UUIDv7](https://www.rfc-editor.org/rfc/rfc9562.html)
- [UUIDv7 vs ULID](https://github.com/uuid6/uuid6-ietf-draft)
- [PostgreSQL UUID performance](https://www.cybertec-postgresql.com/en/uuid-performance/)
