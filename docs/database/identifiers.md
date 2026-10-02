# Identifiers (UUIDv7)

## Why UUIDv7?

For an offline-capable, multi-tenant SaaS, we need identifiers that are:

1. **Globally unique** - No coordination needed between clients
2. **Client-generatable** - Works offline without server round-trip
3. **Time-sortable** - Natural ordering by creation time aids index locality
4. **Collision-resistant** - 122 bits of randomness (74 bits after timestamp)

## UUIDv7 Structure

```
018F4C8E-7B3D-7C8A-9F4E-1A2B3C4D5E6F
│        │        │        │        │
│        │        │        │        └── 48 bits: Random
│        │        │        └────────── 14 bits: Random + Variant (10xx)
│        │        └─────────────────── 4 bits: Version (0111 = 7)
│        └──────────────────────────── 48 bits: Unix timestamp (milliseconds)
└───────────────────────────────────── 48 bits: Unix timestamp (milliseconds)
```

- **48-bit timestamp** (milliseconds since Unix epoch)
- **74 bits randomness** (6 bits version/variant + 68 random)
- **Lexicographically sortable** by creation time
- **RFC 9562 compliant** (published May 2024)

> **Implementation Note:** The custom PostgreSQL `uuid_generate_v7()` function in `infrastructure/database/init/01-extensions.sql` implements a UUIDv7-compatible generator. At the time of writing, it implements the algorithm per the RFC 9562 specification. If the function predates the final RFC publication, verify compatibility with the final RFC 9562 specification.

## PostgreSQL Storage

```sql
-- Column definition
id UUID PRIMARY KEY DEFAULT uuid_generate_v7()

-- Type
id UUID NOT NULL
```

- Use PostgreSQL native `uuid` type (16 bytes)
- Never store as `VARCHAR` or `CHAR(36)`
- Enable `uuid-ossp` and `pgcrypto` extensions

## Generation

### PostgreSQL (Server-side)

```sql
-- Uses custom uuid_generate_v7() function
-- Defined in infrastructure/database/init/01-extensions.sql
DEFAULT uuid_generate_v7()
```

### Laravel (Application-level)

```php
// In model's booted() method
protected static function booted(): void {
    static::creating(function (self $model) {
        if (empty($model->id)) {
            $model->id = Uuid::uuid7()->toString();
        }
    });
}
```

### Offline Clients (Mobile/Desktop)

Clients generate UUIDv7 locally using platform libraries:
- **Dart/Flutter**: `uuid` package
- **C#/.NET**: Custom implementation or NuGet package
- **TypeScript**: `uuid` npm package

## Do NOT Use

| Type | Reason |
|------|--------|
| Auto-increment `BIGSERIAL` | Not globally unique, requires central coordination |
| UUIDv4 (random) | Not sortable, index fragmentation |
| UUIDv1 (MAC + time) | Privacy leak (MAC address), not RFC 9562 |
| UUIDv6 | Superseded by UUIDv7 |
| ULID | Not standard, 10ms precision only |
| NanoID | Not sortable, non-standard |

## UUIDv7 in PostgreSQL

```sql
-- Enable extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Custom uuidv7 function (see infrastructure/database/init/01-extensions.sql)
CREATE OR REPLACE FUNCTION uuid_generate_v7() RETURNS uuid
LANGUAGE plpgsql AS $$
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

-- Usage
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    ...
);
```

## Laravel Model Configuration

```php
class Product extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function booted(): void {
        static::creating(fn ($model) =>
            empty($model->id) && $model->id = Uuid::uuid7()->toString()
        );
    }
}
```

## Verification

```sql
-- Test ordering
SELECT id FROM products ORDER BY id;
-- Should be in creation order (lexicographic = chronological for UUIDv7)
```
