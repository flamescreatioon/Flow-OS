# Local Development Setup Guide

This guide describes how to initialize and run the flowOS local development environment.

Applications (Laravel API, Next.js Web, Flutter Mobile, .NET Desktop) run natively on your workstation, while backing infrastructure (PostgreSQL 16, Redis 7, and Mailpit) runs in Docker Compose.

---

## 1. Prerequisites Check

Before beginning, ensure your workstation meets the required toolchain specifications. Detailed version requirements and platform compatibility are documented in [Prerequisites](prerequisites.md).

Quick check:
```bash
git --version
docker compose version
php -v
composer --version
node -v
npm -v
```
*(For mobile: `flutter doctor`. For desktop: `dotnet --version` on Windows).*

---

## 2. Automated First-Time Setup (Recommended)

flowOS includes a unified setup command that prepares the local development environment safely and idempotently.

### Running Setup

```powershell
# Windows PowerShell
.\scripts\dev.ps1 setup
```
```bash
# Linux / macOS / WSL
./scripts/dev.sh setup
```

### What `dev setup` Does:
1. **Checks toolchain availability** (`git`, `docker`, `node`, `php`, `composer`, `flutter`, `dotnet`).
2. **Copies environment templates** if active configs do not yet exist:
   - `apps/web/.env.example` -> `apps/web/.env.local`
   - `apps/api/.env.example` -> `apps/api/.env`
   *(It never overwrites existing `.env` files).*
3. **Installs application dependencies**:
   - `npm install` in `apps/web`
   - `composer install` in `apps/api`
   - `flutter pub get` in `apps/mobile` (if Flutter is installed)
   - `dotnet restore FlowOS.sln` in `apps/desktop` (if .NET SDK is installed)
4. **Generates Laravel `APP_KEY`** if one is not already set.

### What `dev setup` Does NOT Do:
- It does **not** start Docker containers.
- It does **not** run database migrations.
- It does **not** seed database data.
- It does **not** start web or API development servers.

---

## 3. Starting the Environment (Daily Workflow)

After setup has completed, start your development session in 3 steps:

### Step 1: Start Infrastructure Containers
```powershell
# PowerShell
.\scripts\dev.ps1 up
```
```bash
# Bash
./scripts/dev.sh up
```
Verify that `postgres` and `redis` report `healthy`:
```powershell
.\scripts\dev.ps1 status
```

### Step 2: Run Database Migrations
Apply any pending Laravel schema migrations to the local PostgreSQL database:
```powershell
# PowerShell
.\scripts\dev.ps1 db-migrate
```
```bash
# Bash
./scripts/dev.sh db-migrate
```

### Step 3: Run the Application Runtimes
In separate terminals, start the applications you are working on:

#### Start API Server (Native Host)
```bash
cd apps/api
php artisan serve --host=127.0.0.1 --port=8000
```
Verify API process liveness:
```bash
curl http://127.0.0.1:8000/api/v1/health
# Response: {"status":"ok","service":"flowos-api"}
```

#### Start Web Frontend (Native Host)
```bash
cd apps/web
npm run dev
```
Open `http://localhost:3000` in your web browser.

#### Run Mobile / Desktop (Optional)
- **Mobile**: See [Platform Development Guide](platform-development.md#3-mobile-application-appsmobile) (`flutter run`).
- **Desktop**: See [Platform Development Guide](platform-development.md#4-desktop-application-appsdesktop) (`dotnet run --project src/FlowOS.Desktop/FlowOS.Desktop.csproj`).

---

## 4. Verifying Your Setup

Run the functional test suites across all applications to confirm your workstation is operational:

```powershell
# PowerShell
.\scripts\dev.ps1 test
```
```bash
# Bash
./scripts/dev.sh test
```

Expected result:
- API unit/feature tests: **PASS**
- Web tests: **PASS**
- Mobile tests: **PASS**
- Desktop tests: **PASS** (zero tests discovered)
- Script exit code: **0**

---

## 5. Summary of Default Ports and URLs

| Service / App | Host Address | Access Protocol | Purpose |
|---------------|--------------|-----------------|---------|
| **Next.js Web** | `http://localhost:3000` | HTTP | Frontend user interface |
| **Laravel API** | `http://localhost:8000` | HTTP | REST API gateway |
| **API Healthcheck** | `http://localhost:8000/api/v1/health` | HTTP | Process liveness check |
| **PostgreSQL** | `127.0.0.1:5432` | TCP (PostgreSQL) | Authoritative database |
| **Redis** | `127.0.0.1:6379` | TCP (Redis) | Cache, queues, session store |
| **Mailpit UI** | `http://localhost:8025` | HTTP | Local email dashboard |
| **Mailpit SMTP** | `127.0.0.1:1025` | SMTP | Local email capture |

---

## 6. Next Steps & Related Documentation

- [Prerequisites](prerequisites.md): Tool requirements and installation guide.
- [Environment Configuration](environment.md): Detailed environment variables reference.
- [Docker Infrastructure](docker.md): Managing Compose services and volumes.
- [Database Architecture](database.md): Schema migration, extension, and reset workflows.
- [Platform Development Guide](platform-development.md): Detailed guides for Web, API, Mobile, and Desktop.
- [Development Scripts](scripts.md): Full reference for `dev.ps1` and `dev.sh`.
- [Troubleshooting](troubleshooting.md): Diagnosing common workstation issues.
