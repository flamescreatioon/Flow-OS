# Docker Development Environment

## Overview

flowOS uses Docker Compose to run core backing infrastructure services locally. This ensures that every developer runs the exact same versions of PostgreSQL and Redis without having to install and manage background database servers on their host operating system.

Applications (Laravel API, Next.js Web, Flutter Mobile, and .NET Desktop) run natively on the developer's workstation for optimal debugging, hot-reload, and platform tooling support.

---

## Architecture Topology

```text
┌───────────────────────────────────────────────────────────────┐
│                    Host Machine (Native)                      │
│                                                               │
│   Next.js (Web)       Laravel (API)       Flutter    .NET     │
│   localhost:3000      localhost:8000      (Mobile)   (Desktop)│
│                            │                                  │
│                            │ 127.0.0.1:5432 / 127.0.0.1:6379   │
└────────────────────────────┼──────────────────────────────────┘
                             ▼
┌───────────────────────────────────────────────────────────────┐
│              Docker Compose (Infrastructure)                  │
│   Network: flowos-network                                     │
│                                                               │
│   ┌──────────────────────────┐    ┌─────────────────────────┐ │
│   │        PostgreSQL        │    │          Redis          │ │
│   │     postgres:16-alpine   │    │      redis:7-alpine     │ │
│   │   127.0.0.1:5432:5432    │    │   127.0.0.1:6379:6379   │ │
│   │   Volume: postgres_data  │    │   Volume: redis_data    │ │
│   └──────────────────────────┘    └─────────────────────────┘ │
│                                                               │
│   ┌──────────────────────────┐                                │
│   │         Mailpit          │                                │
│   │   axllent/mailpit:latest │                                │
│   │  SMTP: 127.0.0.1:1025    │                                │
│   │  UI:   127.0.0.1:8025    │                                │
│   └──────────────────────────┘                                │
└───────────────────────────────────────────────────────────────┘
```

---

## Core Infrastructure Services

| Service | Image | Host Port | Internal Port | Persistent Volume | Purpose |
|---------|-------|-----------|---------------|-------------------|---------|
| `postgres` | `postgres:16-alpine` | `127.0.0.1:5432` | `5432` | `postgres_data` | Primary transactional database |
| `redis` | `redis:7-alpine` | `127.0.0.1:6379` | `6379` | `redis_data` | Cache, queues, session store |
| `mailpit` | `axllent/mailpit:latest` | `127.0.0.1:1025`<br>`127.0.0.1:8025` | `1025`<br>`8025` | *None* | Local email capture and web dashboard |

---

## Common Commands

### Starting Infrastructure
```bash
# Start all infrastructure services in the background
docker compose up -d

# Start only core database and cache
docker compose up -d postgres redis
```

### Checking Status and Health
```bash
# View running containers and health status
docker compose ps
```

Expected output:
```text
NAME                IMAGE                  STATUS                    PORTS
flowos-postgres-1   postgres:16-alpine     Up (healthy)              127.0.0.1:5432->5432/tcp
flowos-redis-1      redis:7-alpine         Up (healthy)              127.0.0.1:6379->6379/tcp
flowos-mailpit-1    axllent/mailpit:latest Up                        127.0.0.1:1025->1025/tcp, 127.0.0.1:8025->8025/tcp
```

### Inspecting Logs
```bash
# Follow logs for all services
docker compose logs -f

# Follow logs for a specific service
docker compose logs -f postgres
docker compose logs -f redis
```

### Stopping Infrastructure
```bash
# Stop containers without losing data (volumes preserved)
docker compose down

# Stop a single service
docker compose stop postgres
```

### Restarting Services
```bash
# Restart a service
docker compose restart postgres
docker compose restart redis
```

### Executing Commands in Containers
```bash
# Check PostgreSQL readiness
docker compose exec postgres pg_isready

# Interactive psql shell
docker compose exec postgres psql -U flowos -d flowos

# Redis ping test
docker compose exec redis redis-cli ping

# Interactive redis-cli shell
docker compose exec redis redis-cli
```

---

## Persistent Volumes

Docker uses named volumes to ensure development data survives container restarts:
- `postgres_data` maps to `/var/lib/postgresql/data` in the PostgreSQL container.
- `redis_data` maps to `/data` in the Redis container.

### Persistence Lifecycle
- `docker compose down` stops and removes containers but **retains** the named volumes.
- `docker compose up -d` reattaches the existing volumes with all data intact.
- `docker compose down -v` **deletes** the volumes and all contained data. Use only for clean resets.

---

## Port Configuration & Overrides

By default, ports are bound to loopback `127.0.0.1` to prevent exposing development databases to local networks.

If default ports (5432, 6379) conflict with existing services on your workstation, override them in your root `.env` file without modifying `docker-compose.yml`:

```env
# In root .env
POSTGRES_PORT=5433
REDIS_PORT=6380
```

When overriding ports in root `.env`, update the corresponding port variables in `apps/api/.env`:

```env
# In apps/api/.env
DB_PORT=5433
REDIS_PORT=6380
```
