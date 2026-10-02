# Docker & Infrastructure Troubleshooting

This guide details common issues encountered when running the local flowOS development environment and how to resolve them.

---

## 1. Port Conflicts (5432 or 6379 already in use)

### Symptoms
```text
Error response from daemon: driver failed programming external connectivity on endpoint ...: Bind for 127.0.0.1:5432 failed: port is already allocated
```

### Cause
Another local service (e.g. a native PostgreSQL/Redis service installed on Windows/macOS, or another Docker project) is already listening on port 5432 or 6379.

### Resolution
Do NOT edit `docker-compose.yml`. Instead, define port overrides in your root `.env`:
```env
# In root .env
POSTGRES_PORT=5433
REDIS_PORT=6380
```
Then update your application configuration in `apps/api/.env`:
```env
# In apps/api/.env
DB_PORT=5433
REDIS_PORT=6380
```
Restart the containers:
```bash
docker compose up -d
```

---

## 2. Docker Daemon Not Running / Socket Unavailable

### Symptoms
```text
Server: failed to connect to the docker API at npipe:////./pipe/dockerDesktopLinuxEngine...
Cannot connect to the Docker daemon at unix:///var/run/docker.sock...
```

### Cause
Docker Desktop or the Docker daemon is either stopped, restarting, or has crashed.

### Resolution
1. **Windows**: Launch Docker Desktop from the Start Menu or command line, and wait until the whale icon in the taskbar indicates "Engine running".
2. **WSL2 / Linux**: Verify your user is a member of the `docker` group (`sudo usermod -aG docker $USER`) and check daemon status (`sudo systemctl status docker`).
3. Run `docker info` to verify connectivity before starting containers.

---

## 3. Database Authentication Failure

### Symptoms
```text
SQLSTATE[08006] [7] FATAL: password authentication failed for user "flowos"
```

### Cause
The credentials in `apps/api/.env` (`DB_USERNAME`, `DB_PASSWORD`) do not match the credentials used when the PostgreSQL Docker volume was first initialized.

> [!NOTE]
> PostgreSQL only configures users and passwords from `POSTGRES_USER` and `POSTGRES_PASSWORD` when the data directory is first created. Changing `POSTGRES_PASSWORD` in `.env` later will **not** update the existing volume's password.

### Resolution
1. Verify what password is in your `apps/api/.env`. By default in flowOS, it is `flowos_local` (or `secret`).
2. If you want to sync with a newly changed password in root `.env`, perform a clean volume reset:
   ```bash
   docker compose down -v
   docker compose up -d postgres redis
   cd apps/api && php artisan migrate
   ```

---

## 4. Hostname Confusion: `127.0.0.1` vs Service Names

### Symptoms
```text
SQLSTATE[08006] [7] could not translate host name "postgres" to address: Unknown host
Connection refused: Cannot connect to redis:6379
```

### Cause
Attempting to connect to internal Docker DNS service names (`postgres`, `redis`) from a process running natively on your host workstation (`php artisan serve`, native CLI).

### Resolution
- **From Host Machine** (running Laravel, Flutter, or Next.js on Windows/macOS/Linux):
  - Always use `127.0.0.1` (or `localhost`).
  - Example: `DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1`.
- **From Inside Docker Network** (running within a Compose container):
  - Always use service names.
  - Example: `DB_HOST=postgres`, `REDIS_HOST=redis`.

---

## 5. Migration Error: Table Already Exists

### Symptoms
```text
SQLSTATE[42P07]: Duplicate table: 7 ERROR: relation "organizations" already exists
```

### Cause
PostgreSQL initialized with a legacy SQL script (`02-schema.sql`) or a previous migration run was interrupted, leaving tables present without corresponding entries in the `migrations` tracking table.

### Resolution
Run Laravel's fresh migration command:
```bash
cd apps/api
php artisan migrate:fresh
```
This cleanly drops existing application tables and applies the authoritative Laravel migrations from `apps/api/database/migrations`.

---

## 6. Init Script Changes Not Taking Effect

### Symptoms
You modified `infrastructure/database/init/01-extensions.sql`, but the changes are missing in PostgreSQL.

### Cause
PostgreSQL entrypoint scripts under `/docker-entrypoint-initdb.d` only run during the initial creation of the database cluster.

### Resolution
For local development, perform a volume reset:
```bash
docker compose down -v
docker compose up -d postgres
```
Wait for healthy status, then run `php artisan migrate`.

---

## 7. Inspecting Container Logs

When a service fails to start or healthcheck fails:
```bash
# Check status of containers
docker compose ps

# View last 100 lines of logs for PostgreSQL
docker compose logs --tail=100 postgres

# View last 100 lines of logs for Redis
docker compose logs --tail=100 redis
```
