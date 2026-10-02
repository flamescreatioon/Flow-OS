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
    uuid_bytes :=
        (ts >> 40 & 0xFF)::bytea ||
        (ts >> 32 & 0xFF)::bytea ||
        (ts >> 24 & 0xFF)::bytea ||
        (ts >> 16 & 0xFF)::bytea ||
        (ts >> 8 & 0xFF)::bytea ||
        (ts & 0xFF)::bytea ||
        gen_random_bytes(10);

    -- Set version (7) and variant (RFC 4122)
    uuid_bytes := set_byte(uuid_bytes, 6, (get_byte(uuid_bytes, 6) & 0x0F) | 0x70);
    uuid_bytes := set_byte(uuid_bytes, 8, (get_byte(uuid_bytes, 8) & 0x3F) | 0x80);

    RETURN uuid_bytes::uuid;
END;
$$;
