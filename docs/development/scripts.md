# Development Scripts Reference

flowOS provides cross-platform development automation scripts to manage daily workflows, backing infrastructure, testing, code quality checks, and database operations.

---

## 1. Script Invocations

| Platform | Script Path | Invocation Syntax |
|----------|-------------|-------------------|
| **Windows (PowerShell)** | `scripts/dev.ps1` | `.\scripts\dev.ps1 <command> [args]` |
| **Linux / macOS / WSL (Bash)** | `scripts/dev.sh` | `./scripts/dev.sh <command> [args]` |

Both scripts implement identical command names, arguments, safety validations, and exit codes.

---

## 2. Command Reference Table

| Command | Category | Description | Primary Underlying Tools |
|---------|----------|-------------|--------------------------|
| `setup` | Environment | Prepares development workstation (copies env files, installs deps, generates key) | `npm install`, `composer install`, `flutter pub get`, `dotnet restore` |
| `up` | Infrastructure | Starts PostgreSQL 16, Redis 7, and Mailpit in background | `docker compose up -d` |
| `down` | Infrastructure | Stops infrastructure containers while **preserving persistent volumes** | `docker compose down` |
| `status` | Infrastructure | Displays container status, health, and local access URLs | `docker compose ps` |
| `logs [service]` | Infrastructure | Follows container logs (for all services or a specified service) | `docker compose logs -f [service]` |
| `test` | Quality | Executes all functional test suites across API, Web, Mobile, and Desktop | `php artisan test`, `npm run test`, `flutter test`, `dotnet test` |
| `lint` | Quality | Executes static analysis and linters across API, Web, and Mobile | `pint --test`, `npm run lint`, `flutter analyze` |
| `format` | Quality | Automatically formats code in-place across API, Mobile, and Desktop | `pint`, `dart format`, `dotnet format` |
| `format-check` | Quality | Verifies code formatting without modifying files (fails if unformatted) | `pint --test`, `dart format --output=none`, `dotnet format --verify-no-changes` |
| `verify` | Quality | Full repository-wide quality verification suite | Compose config, API tests+lint, Web tests+lint+typecheck, Mobile tests+analyze, Desktop build+test+format |
| `db-migrate` | Database | Runs pending Laravel schema migrations | `php artisan migrate` |
| `db-status` | Database | Displays migration execution status | `php artisan migrate:status` |
| `db-reset` | Database | **Destructive**: resets local development database schema (requires confirmation) | `php artisan migrate:fresh --force` |
| `help` | Information | Displays usage instructions and supported command summary | None |

---

## 3. Command Details & Semantics

### `setup`
Initializes a new or refreshed developer workspace:
- Scans workstation for prerequisites (`git`, `docker`, `node`, `php`, `composer`, `flutter`, `dotnet`).
- Copies missing `.env` files from templates (`apps/web/.env.local`, `apps/api/.env`).
- Installs application dependencies (`npm install`, `composer install`, `flutter pub get`, `dotnet restore FlowOS.sln`).
- Generates Laravel application encryption key (`php artisan key:generate`) only if missing.
- **Safety**: Never overwrites existing `.env` files; does not start containers or run migrations.

### `test`
Executes functional test suites across all applications where tooling is present:
- **API**: `php artisan test` (SQLite `:memory:`)
- **Web**: `npm run test` (Jest)
- **Mobile**: `flutter test`
- **Desktop**: `dotnet test FlowOS.sln`
- **Exit Code**: Returns `0` if all present suites pass. Does not fail for Flutter analyzer warnings.

### `verify`
Runs the complete repository-wide quality verification pipeline:
1. Docker Compose configuration validation (`docker compose config`).
2. API test suite and Pint code style verification.
3. Web test suite, ESLint, and TypeScript typechecking (`tsc --noEmit`).
4. Mobile test suite and Flutter static analyzer (`flutter analyze`).
5. Desktop build, test execution, and .NET format verification (`dotnet format --verify-no-changes`).

> [!WARNING]
> **Expected Exit Code**: `dev verify` currently exits with code **`1`** due to **accepted Flutter analyzer debt (186 findings)**.
> 
> As defined in `AGENTS.md` Rule 17, local `dev verify` intentionally returns non-zero to maintain visibility into pre-existing technical debt. In hosted CI, `flutter analyze` is temporarily advisory (`continue-on-error: true`). Do not weaken analyzer settings to force exit code 0.

### `db-reset`
Safely resets the local development PostgreSQL schema:
- **Guardrail 1**: Rejects execution if `APP_ENV=production` in `apps/api/.env`.
- **Guardrail 2**: Rejects execution if `DB_HOST` is not `127.0.0.1` or `localhost`.
- **Guardrail 3**: Demands explicit user confirmation (`Type RESET to continue`).
- Drops all application tables and re-runs all migrations (`php artisan migrate:fresh --force`).

---

## 4. Safety Guarantees

1. **Volume Preservation**: `dev down` executes `docker compose down` without the `-v` flag. Development data in PostgreSQL and Redis is preserved across shutdowns.
2. **Environment File Protection**: `dev setup` never overwrites existing `.env` or `.env.local` files.
3. **Graceful Tool Handling**: `dev test` and `dev lint` report warnings and skip components when optional runtimes (e.g. Flutter on a headless server, or .NET on Linux) are not installed. `dev verify` requires all component runtimes to ensure full repository compliance.
4. **Non-Zero Exit Codes**: When any test or verification check fails, scripts return exit code `1`, making them suitable for CI execution.
