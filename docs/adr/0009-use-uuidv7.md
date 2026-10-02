# ADR 0009: Use UUIDv7 for IDs

## Title
Use UUIDv7 for Globally Unique Identifiers

## Status
Accepted

## Context
flowOS requires globally unique IDs for offline-first synchronization:
- Records created offline on multiple devices
- No central ID generator available offline
- IDs must be sortable by creation time
- Must work across all platforms (TypeScript, PHP, Dart, C#)

## Decision
Use **UUIDv7** (RFC 9562) as the primary key strategy for all entities.

### UUIDv7 Structure
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
| **Time-ordered** | Lexicographically sortable by creation time |
| **Globally unique** | No coordination needed |
| **Offline-friendly** | Generate anywhere |
| **Index-friendly** | B-tree friendly (append-only inserts) |
| **Compact** | 16 bytes (36 chars as string) |

## Implementation

### PostgreSQL
```sql
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
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    -- ...
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

class Product extends Model {
    uses UsesUuidv7;
}
```

### Flutter (Dart)
```dart
import 'package:uuid/uuid.dart';

final uuid = Uuid();

// UUIDv7 generation (when available in package)
String generateUuidv7() {
  return Uuid().v7(); // Requires uuid ^4.0
}
```

### .NET (C#)
```csharp
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
import { v7 as uuidv7 } from 'uuid';

const id = uuidv7();
// "018f4c8e-7b3d-7c8a-9f4e-1a2b3c4d5e6f"
```

## Consequences

### Positive
- **No Coordination**: Generate IDs offline on any device
- **Sortable**: Natural ordering by creation time
- **Index Performance**: Append-only B-tree inserts
- **Debugging**: Timestamp embedded in ID
- **Standard**: RFC 9562, growing adoption

### Negative
- **Size**: 36 chars vs 4-8 bytes for integers
- **Complexity**: Custom generation needed
- **Clock Dependency**: Requires reasonably accurate clocks
- **Not Human-Readable**: Unlike sequential IDs

### Mitigations
- Use `CHAR(36)` or `UUID` type in DB
- Ensure NTP on all devices
- Accept size trade-off for offline capability

## Alternatives Considered

### UUIDv4 (Random)
- **Pros**: Standard, widely supported
- **Cons**: Not sortable, index fragmentation, no time info

### ULID
- **Pros**: Sortable, shorter (26 chars), case-insensitive
- **Cons**: Less standard, 10ms precision, no RFC

### NanoID
- **Pros**: Short, URL-safe, fast
- **Cons**: Not sortable, custom alphabet

### Composite Keys
- **Pros**: Natural keys
- **Cons**: Complex queries, ORM issues, not globally unique

### Snowflake IDs
- **Pros**: Sortable, 64-bit integer
- **Cons**: Requires node coordination, not standard

## Testing

```typescript
// Verify time ordering
const id1 = uuidv7();
await delay(1); // 1ms
const id2 = uuidv7();

expect(id1 < id2).toBe(true); // Lexicographic comparison works!
```

## References
- [RFC 9562](https://www.rfc-editor.org/rfc/rfc9562.html)
- [UUIDv7 vs ULID](https://github.com/uuid6/uuid6-ietf-draft)
