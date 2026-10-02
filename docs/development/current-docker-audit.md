# Current Docker Infrastructure Audit

*Sprint 0.16 — Docker Development Environment Audit*

## Overview

This audit assesses the state of local Docker development infrastructure in flowOS, covering Docker Compose configuration, PostgreSQL, Redis, networking, persistent storage, health monitoring, environment variable handling, database initialization vs migrations, and Laravel API connectivity.

---

## Component Audit Matrix

| Component | Status | Details & Observations |
|-----------|--------|-------------------------|
| **Docker Compose Specification** | `IMPLEMENTED` | Modern Compose v2 format without obsolete `version:` field. Parameterized ports, environment interpolation, and restart policies configured. |
| **PostgreSQL Service** | `IMPLEMENTED` | Pinned to `postgres:16-alpine`. Local loopback binding (`127.0.0.1:${POSTGRES_PORT:-5432}:5432`). Named volume persistence. |
| **Redis Service** | `IMPLEMENTED` | Pinned to `redis:7-alpine`. Local loopback binding (`127.0.0.1:${REDIS_PORT:-6379}:6379`). Named volume persistence. |
| **Docker Networking** | `IMPLEMENTED` | Shared bridge network `flowos-network`. Services communicate internally via DNS names (`postgres`, `redis`). |
| **Persistent Volumes** | `IMPLEMENTED` | Named volumes `postgres_data` and `redis_data`. Data preserved across `docker compose down` / `docker compose up -d`. |
| **Healthchecks** | `IMPLEMENTED` | Native healthchecks on both core services: `pg_isready` for PostgreSQL, `redis-cli ping` for Redis. Retries and intervals configured. |
| **Environment Variable Decoupling** | `IMPLEMENTED` | Root `.env` controls Docker Compose (`POSTGRES_*`, `REDIS_*`). `apps/api/.env` controls Laravel API (`DB_*`, `REDIS_*`). Port overrides supported. |
| **Init Scripts vs Migrations Split** | `AUDITED` | `01-extensions.sql` defines extensions and UUIDv7 function. Legacy `02-schema.sql` moved to `infrastructure/database/legacy/`. Laravel migrations are sole schema authority. |
| **Laravel API Integration** | `IMPLEMENTED` | Laravel connects via `127.0.0.1` on host. Zero-dependency process health (`/api/v1/health`) verified independent of DB/Redis. |
| **Optional Tooling Scope** | `IMPLEMENTED` | No unnecessary services (pgAdmin, RedisInsight, Elasticsearch, Kafka, Nginx proxy). Mailpit preserved for email capture. |

---

## Detailed Findings

### 1. Docker Compose Configuration
- **Previous State**:
  - `docker-compose.yml` included top-level `version: '3.8'`, which triggered deprecation warnings in Compose v2.
  - A redundant `docker-compose.override.yml` duplicated the entire configuration, leading to configuration merge conflicts when trying to override ports or credentials via environment variables.
  - Container names were explicitly pinned (`flowos-postgres`, `flowos-redis`), creating naming collisions for developers running multiple clones.
  - Ports were exposed to all network interfaces (`0.0.0.0:5432`, `0.0.0.0:6379`).
- **Remediated State**:
  - Removed top-level `version:` attribute, producing clean `docker compose config` output with zero warnings.
  - Replaced redundant `docker-compose.override.yml` with `docker-compose.override.yml.example`, and added `docker-compose.override.yml` to `.gitignore`.
  - Removed explicit `container_name:` directives; containers use Compose project-scoped naming (`${COMPOSE_PROJECT_NAME:-flowos}-postgres-1`).
  - Parameterized and bound ports to `127.0.0.1` (`127.0.0.1:${POSTGRES_PORT:-5432}:5432` and `127.0.0.1:${REDIS_PORT:-6379}:6379`).

### 2. PostgreSQL Service
- **Image**: `postgres:16-alpine` (explicitly pinned major version with minimal Alpine base).
- **Restart Policy**: `restart: unless-stopped`.
- **Environment**:
  - `POSTGRES_DB: ${POSTGRES_DB:-flowos}`
  - `POSTGRES_USER: ${POSTGRES_USER:-flowos}`
  - `POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-secret}`
- **Volume Mounts**:
  - `postgres_data:/var/lib/postgresql/data` (persistent database storage).
  - `./infrastructure/database/init:/docker-entrypoint-initdb.d:ro` (read-only initialization script mount).
- **Healthcheck**:
  - Command: `pg_isready -U ${POSTGRES_USER:-flowos} -d ${POSTGRES_DB:-flowos}`
  - Interval: 10s, Timeout: 5s, Retries: 5, Start Period: 10s.

### 3. Redis Service
- **Image**: `redis:7-alpine` (explicitly pinned major version).
- **Restart Policy**: `restart: unless-stopped`.
- **Volume Mounts**:
  - `redis_data:/data` (convenience persistence for developer sessions/cache).
- **Healthcheck**:
  - Command: `redis-cli ping` (expected response: `PONG`).
  - Interval: 10s, Timeout: 5s, Retries: 5, Start Period: 5s.

### 4. Database Initialization vs Application Migrations
- **Init SQL (`infrastructure/database/init/`)**:
  - `01-extensions.sql`: Enables `uuid-ossp`, `pgcrypto`, and installs the custom `uuid_generate_v7()` function. This is appropriate as it initializes engine-level capabilities required by database schemas.
- **Legacy Schema (`infrastructure/database/legacy/02-schema.sql`)**:
  - A legacy static SQL dump from early sprints defining tables (`organizations`, `branches`, `users`, `personal_access_tokens`, etc.). **Moved out of active init directory** to prevent automatic execution on fresh volume creation.
- **Application Migrations (`apps/api/database/migrations/`)**:
  - 8 versioned Laravel migrations defining the application schema, including `organization_memberships` and tenant indexes.
- **Architectural Distinction**:
  - Init scripts only execute when a PostgreSQL container data directory is **first initialized**. Modifying SQL in `infrastructure/database/init` has zero effect on an existing volume.
  - Laravel migrations are the authoritative, forward-evolving schema definition for flowOS.
  - The init directory now contains **only true infrastructure prerequisites** (`01-extensions.sql`).

### 5. Host vs Container Networking
- **Host-Native Execution (Current Default)**:
  - Laravel API running via `php artisan serve` connects to `127.0.0.1:5432` and `127.0.0.1:6379`.
- **In-Compose Execution (Future / CI)**:
  - Any container connected to `flowos-network` must connect via service names: `postgres:5432` and `redis:6379`.
- **Documentation**: Both `.env.example` files clearly document this boundary.

### 6. Process Health Independence
- `GET /api/v1/health` in Laravel returns `{ "status": "ok", "service": "flowos-api" }`.
- Verified that the route does not invoke database or Redis connections. Stopping PostgreSQL or Redis does not impact the basic process health check.
