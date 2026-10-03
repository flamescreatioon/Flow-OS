# Development Troubleshooting Guide

This guide details resolutions for common errors and edge cases encountered when running the flowOS local development environment.

---

## 1. Port Conflicts (5432, 6379, 8000, 3000)

### Symptoms
```text
Error response from daemon: Bind for 127.0.0.1:5432 failed: port is already allocated
Failed to listen on 127.0.0.1:8000 (reason: Address already in use)
Error: listen EADDRINUSE: address already in use 127.0.0.1:3000
```

### Diagnosis
Identify which process is currently listening on the conflicting port:
- **Windows (PowerShell)**:
  ```powershell
  Get-NetTCPConnection -LocalPort 5432, 6379, 8000, 3000 -ErrorAction SilentlyContinue |
    Select-Object LocalAddress, LocalPort, OwningProcess, @{Name="ProcessName"; Expression={(Get-Process -Id $_.OwningProcess).ProcessName}}
  ```
- **Linux / macOS**:
  ```bash
  lsof -i :5432 -i :6379 -i :8000 -i :3000
  ```

### Resolution
1. **Never kill unknown system processes indiscriminately**. Verify if a native PostgreSQL or Redis service was previously installed directly on your OS, or if a previous terminal session is still running `php artisan serve` or `npm run dev`.
2. **For Docker services (PostgreSQL / Redis)**: Override the published port in your root `.env` instead of modifying `docker-compose.yml`:
   ```env
   # In root .env
   POSTGRES_PORT=5433
   REDIS_PORT=6380
   ```
   Update `apps/api/.env` to match:
   ```env
   # In apps/api/.env
   DB_PORT=5433
   REDIS_PORT=6380
   ```
   Then restart containers:
   ```powershell
   .\scripts\dev.ps1 up
   ```
3. **For Web / API servers**: If a previous dev server was left running in the background, close that terminal or gracefully terminate that specific process.

---

## 2. Docker Daemon Not Running

### Symptoms
```text
Server: failed to connect to the docker API at npipe:////./pipe/dockerDesktopLinuxEngine...
Cannot connect to the Docker daemon at unix:///var/run/docker.sock...
```

### Resolution
1. Verify Docker status:
   ```bash
   docker info
   ```
2. **Windows**: Launch Docker Desktop and wait until the system tray whale icon shows "Engine running".
3. **Linux**: Ensure the Docker service is running:
   ```bash
   sudo systemctl status docker
   sudo systemctl start docker
   ```
4. Once running, re-run `.\scripts\dev.ps1 up`.

> [!NOTE]
> Standard unit tests (`npm run test`, `php artisan test`, `flutter test`, `dotnet test`) are intentionally decoupled from Docker and run completely offline without requiring the Docker daemon.

---

## 3. Missing or Invalid Laravel `APP_KEY`

### Symptoms
```text
RuntimeException: No application encryption key has been specified.
```

### Cause
The `apps/api/.env` file exists but the `APP_KEY` variable is blank or missing.

### Resolution
Generate an encryption key using Artisan:
```bash
cd apps/api
php artisan key:generate
```
> [!WARNING]
> Only run `key:generate` in your local development environment. Never casually rotate an `APP_KEY` in production, as doing so invalidates all encrypted data, user cookies, and signed URLs.

---

## 4. Next.js Build Artifact & `.next` Cache Locks

### Symptoms
```text
EBUSY: resource busy or locked, rename '.../apps/web/.next/trace'
Failed to compile / EACCES: permission denied
Stale components or hydration errors persisting across code edits
```

### Cause
Next.js caches build and compilation artifacts in `apps/web/.next`. When a running `npm run dev` server or an interrupted `npm run build` process holds open file descriptors on `.next` subdirectories (common on Windows file systems), subsequent build commands fail.

### Resolution
1. Stop any active Next.js development server running in your terminals (`Ctrl+C`).
2. Delete the generated `.next` build output directory:
   - **Windows (PowerShell)**:
     ```powershell
     Remove-Item -Recurse -Force apps/web/.next
     ```
   - **Linux / macOS**:
     ```bash
     rm -rf apps/web/.next
     ```
3. Re-run your check or build:
   ```bash
   cd apps/web
   npm run build
   ```
*(Do not delete source code or `package.json`; `.next` is entirely generated).*

---

## 5. Flutter Build and Artifact Locks

### Symptoms
```text
Waiting for another flutter command to release the build lock...
FileSystemException: Cannot delete file ... build\app\intermediates\...
```

### Cause
A previous `flutter build` or `flutter run` was abruptly terminated while Gradle or the Dart analyzer was accessing files in `apps/mobile/build/` or `.dart_tool/`.

### Resolution
1. Ensure no background Flutter or Dart processes are running.
2. Clean the generated build artifacts:
   ```bash
   cd apps/mobile
   flutter clean
   flutter pub get
   ```
3. If an explicit lock file exists at `<flutter-sdk>/bin/cache/lockfile`, remove it.

---

## 6. Stale MSBuild / .NET Process Locks on Windows

### Symptoms
```text
error MSB3027: Could not copy "..." to "...". Exceeded retry count of 10. Failed.
error MSB3021: Unable to copy file ... because it is being used by another process.
```

### Cause
During C# development and testing in `apps/desktop`, background compiler workers (`VBCSCompiler`) or background test runner processes (`testhost.exe`) may occasionally hold file locks on output DLLs in `bin/` or `obj/` folders.

### Resolution
1. Identify running .NET compiler workers:
   ```powershell
   Get-Process -Name "VBCSCompiler", "testhost" -ErrorAction SilentlyContinue
   ```
2. Stop background compiler workers gracefully:
   ```powershell
   Stop-Process -Name "VBCSCompiler" -Force -ErrorAction SilentlyContinue
   ```
3. Perform a clean rebuild:
   ```bash
   cd apps/desktop
   dotnet clean FlowOS.sln
   dotnet build FlowOS.sln
   ```

---

## 7. Database Connectivity Issues

When Laravel cannot connect to PostgreSQL:
```text
SQLSTATE[08006] [7] could not connect to server: Connection refused
SQLSTATE[08006] [7] FATAL: password authentication failed for user "flowos"
```

### Distinguish Root Causes:
1. **Container Not Running**:
   - Check container status: `docker compose ps`
   - If `postgres` is stopped, run `.\scripts\dev.ps1 up` and check logs: `.\scripts\dev.ps1 logs postgres`.
2. **Hostname Misconfiguration**:
   - When running on host (`php artisan serve`): `DB_HOST` in `apps/api/.env` **must be `127.0.0.1`**, not `postgres`.
3. **Password Mismatch with Volume**:
   - PostgreSQL only initializes credentials when the data volume is first created. If you changed `POSTGRES_PASSWORD` in `.env` after the volume already existed, the database still uses the original password.
   - To align with default credentials: perform a volume reset (`docker compose down -v && docker compose up -d postgres && cd apps/api && php artisan migrate`).

---

## 8. Redis Unavailable vs. Health Endpoint

### Phenomenon
You notice Redis is stopped or unreachable, but `curl http://localhost:8000/api/v1/health` still returns HTTP 200 `{"status":"ok"}`.

### Explanation
This is **intentional architectural design**. As established in **ADR 0016**, Redis is strictly non-authoritative ephemeral infrastructure. The `/api/v1/health` endpoint is a pure process-liveness check and has zero dependencies on Redis or PostgreSQL.
- To verify Redis connectivity directly:
  ```powershell
  docker compose exec redis redis-cli ping
  # Response: PONG
  ```

---

## 9. Diagnosing API Failures Using Request ID

Every HTTP response from the Laravel API includes an `X-Request-ID` header containing a unique UUID:
```http
HTTP/1.1 500 Internal Server Error
X-Request-ID: 01924b11-7390-7000-8000-000000000001
```

### How to Diagnose:
1. Copy the `X-Request-ID` returned by the failed request.
2. Search the Laravel daily log file for the correlating entries:
   ```bash
   # In apps/api
   grep "01924b11-7390-7000-8000-000000000001" storage/logs/laravel-*.log
   ```
3. Structured logs record exception stack traces and contextual tenant IDs tagged with that specific request ID.
> [!SECURITY]
> Never paste or log authentication tokens, passwords, or customer personal data when sharing logs or reporting issues. Use the `X-Request-ID` to pinpoint failures safely.
