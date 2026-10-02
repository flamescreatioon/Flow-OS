# Infrastructure Services

This document details the configuration, operational behavior, and responsibilities of containerized local development services in flowOS.

---

## 1. PostgreSQL Service (`postgres`)

### Specification
- **Docker Image**: `postgres:16-alpine`
- **Restart Policy**: `unless-stopped`
- **Host Binding**: `127.0.0.1:${POSTGRES_PORT:-5432}:5432`
- **Network**: `flowos-network` (bridge)
- **Persistent Volume**: `postgres_data` (`/var/lib/postgresql/data`)
- **Initialization Mount**: `./infrastructure/database/init` -> `/docker-entrypoint-initdb.d:ro`

### Configuration Variables
| Variable | Default (Local) | Source | Description |
|----------|-----------------|--------|-------------|
| `POSTGRES_DB` | `flowos` | Root `.env` | Primary database name |
| `POSTGRES_USER` | `flowos` | Root `.env` | Superuser username |
| `POSTGRES_PASSWORD` | `flowos_local` | Root `.env` | Superuser password |
| `POSTGRES_PORT` | `5432` | Root `.env` | Host-side port mapping |

### Healthcheck Configuration
```yaml
healthcheck:
  test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER:-flowos} -d ${POSTGRES_DB:-flowos}"]
  interval: 10s
  timeout: 5s
  retries: 5
  start_period: 10s
```
The healthcheck leverages `pg_isready` to ping the PostgreSQL server using the configured credentials and database, ensuring that dependent tools only connect when PostgreSQL is accepting client sockets.
### Initialization Behavior (`/docker-entrypoint-initdb.d`)

When the database volume is first created, PostgreSQL executes any `.sql` or `.sh` files placed in `/docker-entrypoint-initdb.d` in alphabetical order:

1. `01-extensions.sql`:
   - Enables `uuid-ossp` and `pgcrypto`.
   - Defines the custom PL/pgSQL function `uuid_generate_v7()`.

> [!IMPORTANT]
> PostgreSQL entrypoint initialization scripts **only run once**, on initial container cluster creation (when `postgres_data` is empty). Editing SQL files under `infrastructure/database/init/` has **no effect** on an existing database volume.
>
> **Active init directory**: `infrastructure/database/init/` contains only `01-extensions.sql`. Legacy schema (`02-schema.sql`) has been moved to `infrastructure/database/legacy/`.
---

## 2. Redis Service (`redis`)

### Specification
- **Docker Image**: `redis:7-alpine`
- **Restart Policy**: `unless-stopped`
- **Host Binding**: `127.0.0.1:${REDIS_PORT:-6379}:6379`
- **Network**: `flowos-network` (bridge)
- **Persistent Volume**: `redis_data` (`/data`)

### Configuration Variables
| Variable | Default (Local) | Source | Description |
|----------|-----------------|--------|-------------|
| `REDIS_PORT` | `6379` | Root `.env` | Host-side port mapping |

### Healthcheck Configuration
```yaml
healthcheck:
  test: ["CMD", "redis-cli", "ping"]
  interval: 10s
  timeout: 5s
  retries: 5
  start_period: 5s
```
Expected response is `PONG`.

### Architectural Role
As defined in **ADR 0016**, Redis is strictly non-authoritative infrastructure:
- **Cache**: Fast key-value storage with tenant-isolated namespaces (`flowos:local:cache:org:{org_id}:...`).
- **Queue**: Asynchronous job transport.
- **Sessions**: Web user session store.
- **Locking**: Short-lived distributed coordination locks with bounded TTLs.
- **Persistence**: While `redis_data` provides convenience across restarts, Redis data must always be treatable as ephemeral and reconstructible from PostgreSQL.

---

## 3. Mailpit Service (`mailpit`)

### Specification
- **Docker Image**: `axllent/mailpit:latest`
- **Restart Policy**: `unless-stopped`
- **Host Bindings**:
  - SMTP: `127.0.0.1:${MAILPIT_PORT:-1025}:1025`
  - Dashboard: `127.0.0.1:${MAILPIT_DASHBOARD_PORT:-8025}:8025`
- **Network**: `flowos-network` (bridge)
- **Persistent Volume**: *None (in-memory dev emails)*

### Architectural Role
Mailpit acts as a local SMTP mock catcher. Any outgoing email sent by the Laravel API during development is captured without reaching real external mail servers, and can be inspected via the web UI at `http://127.0.0.1:8025`.

---

## 4. Internal Docker Network (`flowos-network`)

All services are connected to a shared bridge network named `flowos-network`.
Within this network:
- PostgreSQL is addressable via hostname `postgres` on port `5432`.
- Redis is addressable via hostname `redis` on port `6379`.
- Mailpit is addressable via hostname `mailpit` on port `1025`.
- Runtimes executing on the developer's host machine connect via `127.0.0.1` and published host ports.
