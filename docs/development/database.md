# Database Architecture and Development Workflow

This document details database responsibilities, migration authority, PostgreSQL extension requirements, local operational commands, and seeding status for flowOS.

---

## 1. Database Architecture & Schema Authority

flowOS uses PostgreSQL 16 as the cloud authoritative transactional data store.

### The Source of Truth: Laravel Migrations
- **Canonical Schema Authority**: All database tables, columns, indexes, and constraints are defined exclusively by versioned Laravel migration files located in:
  ```text
  apps/api/database/migrations/
  ```
- **Historical SQL Files**: The file `infrastructure/database/legacy/02-schema.sql` is an archived historical prototype artifact. **Do NOT run or apply legacy SQL files**. Schema evolution must happen solely through Laravel migration files.
- **Rollback Requirements**: Every migration must define an operational `down()` method so changes can be cleanly rolled back:
  ```bash
  cd apps/api
  php artisan migrate:rollback --step=1
  ```

---

## 2. PostgreSQL Extensions (`01-extensions.sql`)

When PostgreSQL boots for the first time with an empty volume (`postgres_data`), it automatically executes SQL files mounted into `/docker-entrypoint-initdb.d/`.

The repository provides:
```text
infrastructure/database/init/01-extensions.sql
```

This file sets up:
1. `uuid-ossp` and `pgcrypto` extensions.
2. The `uuid_generate_v7()` PL/pgSQL function to support UUIDv7 generation directly in PostgreSQL.

> [!IMPORTANT]
> Files in `infrastructure/database/init/` execute **only once**, during the initial cluster creation. Modifying init scripts on an existing container volume has no effect unless the volume is recreated (`docker compose down -v && docker compose up -d`).

---

## 3. Database Migration Commands

flowOS provides cross-platform script wrappers for database tasks.

### Running Migrations
To execute all pending Laravel migrations:
```powershell
# PowerShell
.\scripts\dev.ps1 db-migrate
```
```bash
# Bash
./scripts/dev.sh db-migrate
```
*Direct Artisan equivalent:*
```bash
cd apps/api && php artisan migrate
```

### Checking Migration Status
To view which migrations have executed:
```powershell
# PowerShell
.\scripts\dev.ps1 db-status
```
```bash
# Bash
./scripts/dev.sh db-status
```
*Direct Artisan equivalent:*
```bash
cd apps/api && php artisan migrate:status
```

---

## 4. Resetting the Database (`db-reset`)

When testing schema changes or wiping disposable local data:

```powershell
# PowerShell
.\scripts\dev.ps1 db-reset
```
```bash
# Bash
./scripts/dev.sh db-reset
```

### Safety Safeguards Built into `db-reset`:
The `db-reset` command implements multi-layer guardrails to prevent accidental data destruction:
1. **Production Lock**: Checks `APP_ENV` in `apps/api/.env`. If set to `production`, execution is immediately aborted.
2. **Local Host Check**: Reads `DB_HOST` in `apps/api/.env`. If it is not `127.0.0.1` or `localhost`, execution is refused.
3. **Explicit Confirmation**: The user must explicitly type `RESET` into the terminal before any database operation executes.
4. **Clean Fresh Migration**: Executes `php artisan migrate:fresh --force` to drop all application tables and re-apply all migrations from scratch.

For a deeper comparison between application-level schema resets (`migrate:fresh`) and Docker volume resets (`docker compose down -v`), see the [Database Reset Workflow Guide](database-reset.md).

---

## 5. Database Seeding Status

> [!NOTE]
> **Status: NOT YET ESTABLISHED**
> 
> As of Sprint 0, meaningful domain factories and seeders for business modules (inventory, sales, invoicing) do not yet exist. Seeders will be systematically introduced alongside domain implementations starting in Sprint 1.
> 
> Do not rely on `php artisan db:seed` or `migrate:fresh --seed` for developer setup during Sprint 0.

---

## 6. Financial and Multi-Tenancy Data Rules

When authoring or reviewing database migrations:
1. **Multi-Tenancy Scoping**: Every tenant-owned table must include an `organization_id` foreign key referencing `organizations(id)` with `onDelete('cascade')`.
2. **Authoritative Money Storage**: Never use `float`, `double`, or floating-point column types for financial values.
   - For standard minor-unit currencies (NGN kobo, USD cents), use `bigInteger` storing integer minor units.
   - For high-precision rates, unit prices, or currency exchange rates, use explicit `decimal(p, s)` / `numeric(p, s)`.
   - See [Financial Documentation](../finance/README.md) and [Database Money Guide](../database/money.md).
3. **DateTime Precision**: Use `timestampTz` (UTC) for event timestamps (`created_at`, `updated_at`, `*_at`) and `date` for calendar-only dates. See [DateTime Guidelines](../datetime/README.md).
