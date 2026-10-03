# Environment Variables and Configuration

This guide details the environment configuration model for flowOS, explaining file locations, variable purposes, host-versus-container networking, and security rules.

---

## 1. Environment Files Inventory

flowOS distributes configuration across discrete environment files. This decouples local container infrastructure from individual client applications.

| Path | Purpose | Loaded By | Version Controlled? |
|------|---------|-----------|---------------------|
| `.env.example` | Root template for Docker Compose infrastructure | Reference | **Yes** (Tracked) |
| `.env` | Root active config for Docker Compose | Docker Compose CLI | **No** (Ignored) |
| `apps/api/.env.example` | Template for Laravel backend API | Reference | **Yes** (Tracked) |
| `apps/api/.env` | Active config for Laravel API | Laravel (`Dotenv`) | **No** (Ignored) |
| `apps/web/.env.example` | Template for Next.js web application | Reference | **Yes** (Tracked) |
| `apps/web/.env.local` | Active config for Next.js web application | Next.js runtime | **No** (Ignored) |
| `apps/mobile/.env.example` | Template for mobile client build parameters | Dart define / config | **Yes** (Tracked) |
| `apps/desktop/.env.example` | Template for desktop client configuration | .NET app configuration | **Yes** (Tracked) |

---

## 2. Host vs. Container Networking

Understanding host vs. container network address resolution is critical when developing locally:

```text
┌─────────────────────────────────────────────────────────────┐
│ Developer Workstation (Host OS)                             │
│                                                             │
│   Laravel API (php artisan serve):                          │
│     DB_HOST=127.0.0.1        (connects to published port)   │
│     REDIS_HOST=127.0.0.1     (connects to published port)   │
│                                                             │
│   Next.js Web (npm run dev):                                │
│     NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1        │
│                                                             │
│   Flutter Mobile (Android Emulator):                        │
│     API_BASE_URL=http://10.0.2.2:8000/api/v1 (Host loopback)│
└───────────────────────────┬─────────────────────────────────┘
                            │ Port 5432 / 6379 (Bound to 127.0.0.1)
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ Docker Compose Bridge Network (flowos-network)              │
│                                                             │
│   Container-to-container internal communication uses:       │
│     postgres:5432                                           │
│     redis:6379                                              │
│     mailpit:1025                                            │
└─────────────────────────────────────────────────────────────┘
```

### Key Networking Rules:
1. **Host-Running Applications (Default Dev Mode)**:
   - When running `php artisan serve` on your host machine, set `DB_HOST=127.0.0.1` and `REDIS_HOST=127.0.0.1`.
   - Never set `DB_HOST=postgres` when executing Laravel directly on the host machine; host OS DNS cannot resolve internal Docker container names.
2. **Containerized Execution**:
   - If an application is explicitly executed inside the Docker network (e.g. within a future worker container), set `DB_HOST=postgres` and `REDIS_HOST=redis`.
3. **Android Emulator Access**:
   - The Android virtual device emulator routes host loopback traffic through `10.0.2.2`. Therefore, the mobile application uses `http://10.0.2.2:8000/api/v1` during Android emulator testing.

---

## 3. Configuration Reference

### Docker Infrastructure (Root `.env`)

| Variable | Required | Default (Local) | Sensitive | Description |
|----------|----------|-----------------|-----------|-------------|
| `POSTGRES_DB` | Yes | `flowos` | No | PostgreSQL database name |
| `POSTGRES_USER` | Yes | `flowos` | No | PostgreSQL superuser username |
| `POSTGRES_PASSWORD` | Yes | `flowos_local` | **Yes** | PostgreSQL password (local dev only) |
| `POSTGRES_PORT` | No | `5432` | No | Host loopback port published by PostgreSQL |
| `REDIS_PORT` | No | `6379` | No | Host loopback port published by Redis |
| `MAILPIT_PORT` | No | `1025` | No | Host loopback SMTP port published by Mailpit |
| `MAILPIT_DASHBOARD_PORT` | No | `8025` | No | Host loopback HTTP port for Mailpit UI |

---

### Backend API (`apps/api/.env`)

| Variable | Required | Default (Local) | Sensitive | Description |
|----------|----------|-----------------|-----------|-------------|
| `APP_NAME` | No | `flowOS` | No | Application display name |
| `APP_ENV` | Yes | `local` | No | Environment mode (`local`, `testing`, `production`) |
| `APP_KEY` | Yes | *Auto-generated* | **Yes** | 32-character AES encryption key |
| `APP_DEBUG` | Yes | `true` | No | Detailed stack traces in HTTP responses |
| `APP_URL` | Yes | `http://localhost:8000` | No | Base URL of the API server |
| `DB_CONNECTION` | Yes | `pgsql` | No | Database driver (`pgsql` for development) |
| `DB_HOST` | Yes | `127.0.0.1` | No | Host loopback address for PostgreSQL |
| `DB_PORT` | Yes | `5432` | No | Port mapped to PostgreSQL container |
| `DB_DATABASE` | Yes | `flowos` | No | Target database matching root `.env` |
| `DB_USERNAME` | Yes | `flowos` | No | Target user matching root `.env` |
| `DB_PASSWORD` | Yes | `flowos_local` | **Yes** | Target password matching root `.env` |
| `REDIS_HOST` | Yes | `127.0.0.1` | No | Host loopback address for Redis |
| `REDIS_PORT` | Yes | `6379` | No | Port mapped to Redis container |
| `REDIS_PASSWORD` | No | `null` | **Yes** | Redis authentication password |
| `CACHE_DRIVER` | Yes | `redis` | No | Cache driver (Redis for dev) |
| `QUEUE_CONNECTION` | Yes | `redis` | No | Job queue connection |
| `SESSION_DRIVER` | Yes | `redis` | No | Web session persistence |
| `SANCTUM_STATEFUL_DOMAINS` | Yes | `localhost:3000,127.0.0.1:3000` | No | Allowed SPA origins for cookie session auth |
| `CORS_ALLOWED_ORIGINS` | Yes | `http://localhost:3000,http://127.0.0.1:3000` | No | Allowed CORS origin headers |

---

### Web Application (`apps/web/.env.local`)

| Variable | Required | Default (Local) | Sensitive | Description |
|----------|----------|-----------------|-----------|-------------|
| `NEXT_PUBLIC_API_URL` | Yes | `http://localhost:8000/api/v1` | **No (Public)** | API endpoint prefix bundled into client JavaScript |
| `NEXT_PUBLIC_APP_URL` | No | `http://localhost:3000` | **No (Public)** | Frontend origin URL |

> [!WARNING]
> Variables prefixed with `NEXT_PUBLIC_` are baked directly into client-side JavaScript bundles by Next.js and are visible to anyone inspecting web network requests or source scripts. **Never** prefix secrets, database passwords, or private service keys with `NEXT_PUBLIC_`.

---

### Mobile Application (`apps/mobile/.env.example` / Build Arguments)

| Variable | Required | Default (Local) | Description |
|----------|----------|-----------------|-------------|
| `API_BASE_URL` | Yes | `http://10.0.2.2:8000/api/v1` | Target API gateway (use `10.0.2.2` for Android emulator, `localhost` for iOS simulator) |
| `APP_ENV` | No | `development` | Target client environment profile |

Mobile build configuration can be supplied at build/run time via:
```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

---

### Desktop Application (`apps/desktop/.env.example` / `appsettings.json`)

| Setting | Required | Default (Local) | Description |
|---------|----------|-----------------|-------------|
| `Api:BaseUrl` | Yes | `http://localhost:8000/api/v1` | Desktop client target API endpoint |
| `ConnectionStrings:DefaultConnection` | No | `flowos.db` | Local SQLite file path for future offline cache |
| `Logging:LogLevel` | No | `Debug` | Local diagnostic log verbosity |

---

## 4. Security Rules for Environment Management

1. **Never Commit Secret Credentials**:
   - Never commit `.env` or `.env.local` files to version control.
   - All `.env` files except `.env.example` templates are ignored by `.gitignore`.
2. **Local Dummy Passwords Only**:
   - Default passwords in `.env.example` (such as `flowos_local`) are intended strictly for disposable local workstations.
3. **Laravel `APP_KEY`**:
   - The application key encrypts cookies, signed URLs, and session data.
   - Run `php artisan key:generate` to generate an isolated key locally.
   - Never copy or rotate an active production `APP_KEY` without planned migration procedures.
4. **Separation of Tenant Context**:
   - Client applications never pass `organization_id` or database credentials in environment variables. Tenant context is authenticated at runtime via session cookies or bearer tokens.
