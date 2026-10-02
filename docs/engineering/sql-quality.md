# SQL and Migration Quality

PostgreSQL is authoritative. Laravel migrations are the schema evolution mechanism; raw SQL is reserved for cases where it is clearer or necessary and must remain parameterized, tenant-safe, and PostgreSQL-correct. See the database standards for exact schema details.

Each migration has one clear responsibility, deliberate constraints and indexes, a practical `down()` method, and a safe strategy for destructive or high-volume changes. Prefer additive/backfill/constrain stages. Tenant-owned tables and queries use organization scope; client-supplied organization IDs are never trusted. Identifiers are `snake_case`, following [database naming conventions](../database/naming-conventions.md).

Use UUIDv7-style business IDs, UTC instants for `*_at`, DATE for calendar values, IANA timezone IDs, and exact money representation: integer minor units or a field-specific numeric/decimal type. Test rollback where practical. SQLite convenience never overrides PostgreSQL correctness.
