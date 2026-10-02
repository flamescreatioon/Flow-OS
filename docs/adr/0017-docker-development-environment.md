# ADR 0017: Docker Development Environment

## Title

Establish local Docker development environment for core infrastructure services (PostgreSQL and Redis) while running application runtimes natively on the host machine.

## Status

ACCEPTED

## Context

flowOS requires local infrastructure services to support API, web, mobile, and desktop development:
- **PostgreSQL 16+** as the authoritative relational database engine with UUIDv7 support and multi-tenant schema isolation.
- **Redis 7+** as non-authoritative infrastructure for caching, queue transport, session state, and short-lived distributed locking.
- **Mailpit** for local SMTP email interception and preview during development.

Development environments differ widely across team members (Windows with Docker Desktop/WSL2, macOS, Linux). A reliable, reproducible local setup is needed that:
1. Prevents environment divergence and "works on my machine" issues.
2. Avoids manual local installation and service management of PostgreSQL and Redis on the developer's host OS.
3. Does not add unnecessary containerization overhead to UI application frameworks (Next.js, Flutter, WPF) where native hot reload, platform SDK access, and device emulation are critical.
4. Distinguishes infrastructure container configuration from native application configuration (host vs container hostnames and ports).
5. Establishes a clean division of responsibility between container-level database initialization (extensions and custom functions) and application schema evolution (Laravel migrations).

## Decision

1. **Docker Compose v2 for Core Infrastructure Only**
   - Use modern Docker Compose specification syntax (no obsolete `version:` attribute).
   - Core containerized services are strictly limited to:
     - `postgres` (`postgres:16-alpine`)
     - `redis` (`redis:7-alpine`)
     - `mailpit` (`axllent/mailpit:latest` for email capture)
   - Do NOT containerize Next.js, Flutter, or .NET desktop applications.
   - Do NOT introduce unapproved services (pgAdmin, RedisInsight, Elasticsearch, Kafka, RabbitMQ, MinIO, Nginx reverse proxy).

2. **Native Application Runtimes on Host Machine**
   - The default development topology is:
     - **Docker containers**: PostgreSQL, Redis, Mailpit.
     - **Host machine**: Laravel API (`php artisan serve`), Next.js Web (`npm run dev`), Flutter Mobile (`flutter run`), and .NET Desktop (`dotnet run`).
   - Host applications connect to Docker services via loopback (`127.0.0.1`).
   - If an application is run inside Compose in future environments, it connects using Compose service names (`postgres`, `redis`).

3. **Port Binding and Override Strategy**
   - Service ports bind explicitly to `127.0.0.1` on the host to avoid exposing database/cache ports to external network interfaces:
     - PostgreSQL: `127.0.0.1:${POSTGRES_PORT:-5432}:5432`
     - Redis: `127.0.0.1:${REDIS_PORT:-6379}:6379`
     - Mailpit: `127.0.0.1:${MAILPIT_PORT:-1025}:1025` and `127.0.0.1:${MAILPIT_DASHBOARD_PORT:-8025}:8025`
   - Port conflicts can be resolved via environment variables in root `.env` without modifying `docker-compose.yml`.

4. **Persistence via Named Volumes**
   - Persistent development data uses Docker named volumes (`postgres_data`, `redis_data`).
   - Standard startup/teardown (`docker compose down` and `docker compose up -d`) preserves data.
   - Destructive volume removal (`docker compose down -v`) is reserved for intentional clean-slate resets.
   - Do not bind database internal data directories directly to host filesystem paths on Windows to avoid performance degradation and permission anomalies.

5. **Service Healthchecks**
   - PostgreSQL: `pg_isready -U ${POSTGRES_USER:-flowos} -d ${POSTGRES_DB:-flowos}` with 10s interval, 5s timeout, 5 retries, and 10s start period.
   - Redis: `redis-cli ping` with 10s interval, 5s timeout, 5 retries, and 5s start period.
   - Both services use `restart: unless-stopped`.

6. **Predictable Shared Bridge Network**
   - All services attach to `flowos-network` (bridge driver).
   - Compose-generated container names avoid naming collisions when developers run multiple checkouts.

7. **Schema Evolution and Initialization Split**
   - **Database Initialization SQL** (`infrastructure/database/init/01-extensions.sql` mounted read-only to `/docker-entrypoint-initdb.d`):
     - Establishes foundational engine-level extensions (`uuid-ossp`, `pgcrypto`).
     - Establishes custom engine functions (`uuid_generate_v7()`).
     - Note: Init scripts only execute when a volume is first created.
   - **Application Schema Evolution** (`apps/api/database/migrations`):
     - Laravel migrations are the sole authoritative mechanism for application tables, foreign keys, indexes, and triggers.
     - Legacy init schema SQL (`02-schema.sql`) has been moved to `infrastructure/database/legacy/` and is no longer mounted in the initialization path.

8. **Application Health Independence**
   - The basic process health endpoint (`GET /api/v1/health`) must remain independent of PostgreSQL and Redis availability.
   - Container healthchecks monitor infrastructure services; Laravel process health monitors the HTTP runtime.

## Consequences

### Positive
- Standardized, repeatable environment setup across all operating systems.
- Fast onboarding: `cp .env.example .env && docker compose up -d postgres redis`.
- Host hot-reload and native debugging remain fast and uninterrupted for web, mobile, and desktop.
- No risk of host port collisions locking out developers thanks to environment-driven port overrides.
- Database extensions and UUIDv7 custom function are automatically loaded on fresh volume creation.

### Negative / Trade-offs
- Developers must maintain separate `.env` files for Docker root infrastructure and native applications (`apps/api/.env`, `apps/web/.env.local`).
- Editing database init scripts does not affect existing Docker volumes without a volume rebuild or migration.
- Developers must have Docker Desktop or Docker engine installed locally.
