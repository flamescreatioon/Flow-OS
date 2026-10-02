# Database Reset Workflow

This guide explains how to safely reset the local development database in flowOS, distinguishing between application schema resets and full Docker volume deletions.

---

## 1. Application-Level Schema Reset (Recommended)

When you need to re-run all migrations from scratch without destroying the underlying PostgreSQL container or wiping engine-level extensions (`uuid-ossp`, `pgcrypto`, `uuid_generate_v7()`), use Laravel's `migrate:fresh` command.

```bash
cd apps/api
php artisan migrate:fresh
```

### With Seed Data (Optional)
If seeders are configured and desired:
```bash
php artisan migrate:fresh --seed
```

### Why this is the preferred approach:
- **Fast**: Takes 1–2 seconds.
- **Safe**: Drops only application tables; does not drop the database or database-level extensions.
- **Preserves Configuration**: The PostgreSQL container stays running and healthy.

---

## 2. Full Infrastructure Volume Reset (Destructive)

When you need to completely discard all local data, purge corrupt state, or test first-time initialization from scratch, you can remove the Docker persistent volumes.

> [!WARNING]
> This destroys all PostgreSQL database tables, records, users, and Redis cache/session keys in your local environment. Do not perform this if you have local data you wish to keep. Never run destructive operations against non-local environments.

### Step-by-Step Volume Reset

#### 1. Stop containers and destroy named volumes
```bash
# In repository root:
docker compose down -v
```
The `-v` (or `--volumes`) flag instructs Docker Compose to delete named volumes (`postgres_data`, `redis_data`).

#### 2. Recreate containers with clean volumes
```bash
docker compose up -d postgres redis
```

#### 3. Wait for PostgreSQL to become healthy
```bash
docker compose ps
```
Wait until `postgres` reports `Up (healthy)`. During this initial boot, PostgreSQL will execute `01-extensions.sql` to install `uuid-ossp`, `pgcrypto`, and the `uuid_generate_v7()` function.

#### 4. Run Laravel migrations
```bash
cd apps/api
php artisan migrate
```

Verify migration status:
```bash
php artisan migrate:status
```

---

### 3. Important Notes on Initialization Scripts

- **One-time execution**: PostgreSQL entrypoint scripts under `infrastructure/database/init/` (`01-extensions.sql`) **only run on volume creation**.
- **Modifying init scripts**: If you edit a file in `infrastructure/database/init/`, your existing Docker database will **not** reflect those changes until you perform a volume reset (`docker compose down -v && docker compose up -d`) or write a corresponding Laravel migration.
- **Active init directory**: Currently contains only `01-extensions.sql` (PostgreSQL extensions and `uuid_generate_v7()` function). Legacy schema (`02-schema.sql`) has been moved to `infrastructure/database/legacy/`.
- **Authoritative Schema**: Application tables must always be modified via versioned Laravel migrations in `apps/api/database/migrations`, never by editing init SQL scripts.

---

## 4. Redis Cache / Session Reset

To clear development cache and sessions without affecting PostgreSQL:

### Via Laravel Artisan
```bash
cd apps/api
php artisan cache:clear
```

### Direct via redis-cli (Development Only)
```bash
docker compose exec redis redis-cli flushall
```
> [!CAUTION]
> `FLUSHALL` is destructive and clears all Redis keys across all logical databases. Only use this command in local development.
