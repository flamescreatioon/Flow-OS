# Database Documentation

This directory contains all database standards, conventions, and reference documentation for flowOS.

## Quick Links

| Document | Description |
|----------|-------------|
| [naming-conventions.md](naming-conventions.md) | Table, column, index, and constraint naming rules |
| [identifiers.md](identifiers.md) | UUIDv7 strategy for business records |
| [timestamps.md](timestamps.md) | Timestamp conventions and timezone handling |
| [money.md](money.md) | Money storage rules (integer minor units, NUMERIC) |
| [tenancy.md](tenancy.md) | Multi-tenancy rules (organization_id, branch_id) |
| [migrations.md](migrations.md) | Migration rules, naming, reversibility |
| [indexes.md](indexes.md) | Indexing strategy and patterns |
| [foreign-keys.md](foreign-keys.md) | Foreign key conventions and delete behaviors |
| [soft-deletes.md](soft-deletes.md) | Soft delete patterns and when to use them |
| [audit-fields.md](audit-fields.md) | Created/updated/deleted fields and sync metadata |
| [constraints.md](constraints.md) | NOT NULL, UNIQUE, CHECK, FOREIGN KEY rules |
| [development-database.md](development-database.md) | Local PostgreSQL setup |
| [current-schema-audit.md](current-schema-audit.md) | Audit of current database schema |

## Quick Reference

### PostgreSQL Version
- **Primary**: PostgreSQL 16+
- **Driver**: Laravel's pgsql driver
- **Extensions**: uuid-ossp, pgcrypto (for uuidv7)

### Current Schema
See [current-schema-audit.md](current-schema-audit.md) for existing tables.

### Key Principles

1. **PostgreSQL is the source of truth** for cloud data
2. **UUIDv7** for all business record primary keys
3. **organization_id** on all tenant-owned tables
4. **Integer minor units** for money (kobo for NGN)
5. **timestamptz** for timestamps (UTC)
6. **Soft deletes** only where recovery is needed
7. **Foreign keys** for all relationships
8. **Migrations** must be reversible

## Current Schema

### Existing Tables (from migrations)

| Table | Purpose | Primary Key | Tenancy |
|---------|---------|-------------|---------|
| `organizations` | Tenant root | UUIDv7 | Root tenant |
| `branches` | Branch locations | UUIDv7 | `organization_id` |
| `users` | Staff/users | UUIDv7 | `organization_id` |
| `personal_access_tokens` | API tokens | UUIDv7 | Polymorphic |
| `password_reset_tokens` | Password reset | Email (PK) | Global |
| `failed_jobs` | Queue failures | BIGSERIAL | Global |
| `jobs` | Queue jobs | BIGSERIAL | Global |
| `migrations` | Migration tracking | SERIAL | Global |

### Key Columns (Standard)

| Column | Type | Required |
|---------|------|----------|
| `id` | UUID (v7) | PK |
| `organization_id` | UUID | FK, tenant scope |
| `created_at` | TIMESTAMPTZ | Auto |
| `updated_at` | TIMESTAMPTZ | Auto trigger |
| `deleted_at` | TIMESTAMPTZ | Soft delete |
| `synced_at` | TIMESTAMPTZ | Sync metadata |

### Conventions

| Element | Convention |
|---------|------------|
| Tables | `snake_case`, plural |
| Columns | `snake_case` |
| PK | `id` UUIDv7 |
| FK | `{table}_id` |
| Indexes | `idx_{table}_{cols}` |
| Unique | `uq_{table}_{cols}` |
| FK | `fk_{src}_{ref}` |

### Timestamps

```sql
created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
deleted_at TIMESTAMPTZ,  -- NULL = active
synced_at TIMESTAMPTZ,   -- For offline sync
```

### Money

```sql
-- Integer minor units (preferred for operations)
price_in_minor_units BIGINT NOT NULL,
currency CHAR(3) NOT NULL DEFAULT 'NGN',

-- Or for accounting precision (field-specific precision/scale)
amount NUMERIC(19,4) NOT NULL,
currency CHAR(3) NOT NULL DEFAULT 'NGN',
```

### Default Currency
- **NGN** (Nigerian Naira)
- Minor unit: Kobo (100 = ₦1.00)

## Quick Commands

```bash
# Local development
cd apps/api
docker-compose up -d
php artisan migrate:fresh --seed
php artisan test

# Migrations
php artisan migrate
php artisan migrate:rollback --step=1

# Tests
php artisan test
```

## Documentation Index

| File | Description |
|------|-------------|
| [naming-conventions.md](naming-conventions.md) | Table, column, index naming rules |
| [identifiers.md](identifiers.md) | UUIDv7 strategy |
| [timestamps.md](timestamps.md) | Timestamp conventions |
| [money.md](money.md) | Money storage rules |
| [tenancy.md](tenancy.md) | Multi-tenancy rules |
| [migrations.md](migrations.md) | Migration rules |
| [indexes.md](indexes.md) | Indexing strategy |
| [foreign-keys.md](foreign-keys.md) | FK conventions |
| [soft-deletes.md](soft-deletes.md) | Soft delete patterns |
| [audit-fields.md](audit-fields.md) | Audit fields and sync metadata |
| [constraints.md](constraints.md) | Constraint rules |
| [development-database.md](development-database.md) | Local setup |
| [current-schema-audit.md](current-schema-audit.md) | Existing schema |