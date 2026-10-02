# ADR 0004: Use PostgreSQL

## Title
Use PostgreSQL as Primary Database

## Status
Accepted

## Context
Need a relational database for the SaaS application with:
- ACID compliance for financial data
- JSONB for flexible schemas
- Multi-tenancy support
- UUID support
- Strong indexing
- Mature ecosystem

## Decision
Use **PostgreSQL 16+** as the primary database.

## Consequences

### Positive
- **ACID**: Strong consistency for financial transactions
- **JSONB**: Flexible schemas for settings, metadata
- **UUID**: Native UUID type with uuidv7 support
- **Indexing**: Partial, expression, GIN indexes
- **Extensions**: PostGIS, pg_trgm, uuid-ossp
- **Multi-tenancy**: Row-level security, advisory locks
- **Open Source**: No vendor lock-in
- **Cloud**: Available on all major clouds

### Negative
- **Horizontal Scaling**: Read replicas only, sharding manual
- **Memory**: Higher than MySQL
- **Vacuum**: Autovacuum tuning needed
- **Connection Limits**: Process-per-connection model

### Mitigations
- PgBouncer for connection pooling
- Read replicas for read-heavy workloads
- Proper indexing strategy
- Monitoring with pg_stat_statements

## Alternatives Considered

### MySQL 8
- **Pros**: Wide adoption, good performance
- **Cons**: Weaker JSON support, no partial indexes, GTID replication complexity

### SQLite
- **Pros**: Embedded, zero config
- **Cons**: Not for multi-user server, no network access

### MongoDB
- **Pros**: Flexible schema, horizontal scaling
- **Cons**: No ACID transactions (historically), eventual consistency, not ideal for relational data

### CockroachDB
- **Pros**: Distributed SQL, strong consistency
- **Cons**: Younger, smaller ecosystem, cost

## UUIDv7 Implementation

```sql
-- Custom function for UUIDv7 (timestamp-ordered)
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
```

## References
- [PostgreSQL 16](https://www.postgresql.org/docs/16/)
- [UUIDv7](https://www.rfc-editor.org/rfc/rfc9562.html)
