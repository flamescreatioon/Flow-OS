-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Create uuidv7 function (PostgreSQL 16+ has gen_random_uuid but not uuidv7)
-- Using a custom implementation for uuidv7
CREATE OR REPLACE FUNCTION uuid_generate_v7()
RETURNS uuid
LANGUAGE plpgsql
AS $$
DECLARE
    ts bigint;
    uuid_bytes bytea;
BEGIN
    -- Get current timestamp in milliseconds
    ts := (EXTRACT(EPOCH FROM clock_timestamp()) * 1000)::bigint;

    -- Build UUID v7: 48-bit timestamp + 74 random bits + version/variant
    -- int8send returns the 8-byte big-endian representation; bytes 3-8 are
    -- the low 48 bits of the millisecond timestamp. PostgreSQL has no
    -- direct bigint-to-bytea cast, so per-byte casts cannot be used.
    uuid_bytes :=
        substring(int8send(ts) from 3 for 6) ||
        gen_random_bytes(10);

    -- Set version (7) and variant (RFC 4122)
    uuid_bytes := set_byte(uuid_bytes, 6, (get_byte(uuid_bytes, 6) & 0x0F) | 0x70);
    uuid_bytes := set_byte(uuid_bytes, 8, (get_byte(uuid_bytes, 8) & 0x3F) | 0x80);

    -- bytea cannot cast to uuid directly; route through hex text
    RETURN encode(uuid_bytes, 'hex')::uuid;
END;
$$;
