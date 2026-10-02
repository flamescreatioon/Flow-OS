# Continuous Integration (CI) Architecture

This document defines the GitHub Actions CI system for **flowOS**.

---

## 1. Objectives & Principles

The flowOS CI pipeline automatically validates all pull requests and pushes to `main`. It guarantees that:
- Code builds and compiles without regressions across all supported platforms.
- Automated tests pass deterministically.
- Code style, formatting, and type checks adhere to monorepo coding standards.
- Disposable infrastructure services (PostgreSQL 16, Redis 7) boot properly and accept migrations.
- No release artifacts, production packages, or production deployments are triggered by CI.

---

## 2. Workflow Triggers & Concurrency

- **Triggers**:
  - `pull_request`: Runs on all PRs targeting `main`.
  - `push`: Runs on every direct commit or merged PR on `main`.
  - `workflow_dispatch`: Enables manual triggering for ad-hoc verification.
- **Permissions**: Minimum least-privilege token access (`contents: read`).
- **Concurrency**:
  ```yaml
  concurrency:
    group: ci-${{ github.workflow }}-${{ github.ref }}
    cancel-in-progress: true
  ```
  Obsolete runs on the same branch or PR are cancelled immediately when newer commits are pushed, conserving CI minutes.

---

## 3. CI Topology & Job Matrix

The CI workflow is configured in `.github/workflows/ci.yml` and consists of six parallel, independent jobs:

| Job ID | Visible Name | Runner OS | Timeout | Blocking? | Core Responsibilities |
|--------|--------------|-----------|---------|-----------|------------------------|
| `repository` | **Repository** | `ubuntu-latest` | 10m | **Yes** | Docker Compose validation (`docker compose config`), bash script syntax check (`bash -n`), merge conflict marker scan. |
| `api` | **API** | `ubuntu-latest` | 15m | **Yes** | PHP 8.2 setup, Composer cache/install, fast SQLite test suite (`php artisan test`), Pint style check (`pint --test`). |
| `web` | **Web** | `ubuntu-latest` | 15m | **Yes** | Node 20 setup, npm cache/install (`npm ci`), Jest financial tests (`npm run test`), ESLint (`npm run lint`), TypeScript (`npm run typecheck`), Next.js production build (`npm run build`). |
| `mobile` | **Mobile** | `ubuntu-latest` | 20m | **Yes** | Flutter stable channel, dependency resolution (`flutter pub get`), Dart formatting (`dart format`), unit/widget tests (`flutter test`), temporary advisory analyze. |
| `desktop` | **Desktop** | `windows-latest` | 20m | **Yes** | .NET 8 SDK on Windows runner (for WPF), NuGet restore, Release build (`dotnet build`), test execution, C# format check (`dotnet format --verify-no-changes`). |
| `infrastructure` | **Infrastructure** | `ubuntu-latest` | 15m | **Yes** | PostgreSQL 16 & Redis 7 service containers, Redis ping, extension installation (`01-extensions.sql`), UUIDv7 verification, Laravel PostgreSQL migration smoke (`php artisan migrate --force`). |

---

## 4. Runtime Versions & Tooling

| Tool / Runtime | Version Pinned in CI | Rationale |
|----------------|----------------------|-----------|
| **PHP** | `8.2` | Matches `apps/api/composer.json` requirement (`^8.2`). |
| **Node.js** | `20` (LTS) | Matches Next.js and frontend dev requirement. |
| **Flutter** | `stable` | Matches Flutter SDK requirement (`>=3.3.0 <4.0.0`). |
| **.NET SDK** | `8.0.x` | Matches .NET 8 WPF desktop application. |
| **PostgreSQL** | `16-alpine` | Matches development Docker Compose service version. |
| **Redis** | `7-alpine` | Matches development Docker Compose service version. |

---

## 5. Quality Debt Exception: Flutter Analyzer

> [!WARNING]
> **Temporary Quality Debt Exception**: The `flutter analyze` step currently surfaces 185 pre-existing code style findings.
> In `.github/workflows/ci.yml`, this step is configured with:
> ```yaml
> - name: Flutter analyze (Temporary quality debt exception)
>   run: flutter analyze
>   continue-on-error: true
> ```
> - **Behavior**: The analyzer output is printed visibly in GitHub Actions logs, but a non-zero exit code does NOT fail the `Mobile` job.
> - **Distinction**: Local `./scripts/dev.sh verify` (or `.\scripts\dev.ps1 verify`) remains strictly non-zero (`exit 1`) to preserve local visibility of quality debt.
> - **Exit Criterion**: Once all 185 findings are resolved in a subsequent cleanup sprint, `continue-on-error: true` will be immediately removed and `flutter analyze` will become a hard blocking check.

---

## 6. Infrastructure Smoke vs. Integration Testing

The `Infrastructure` job uses ephemeral GitHub Actions service containers to smoke-test foundational database dependencies:
1. **Redis Smoke**: Executes `redis-cli ping` to confirm container responsiveness.
2. **PostgreSQL Extension Bootstrap**: Executes `infrastructure/database/init/01-extensions.sql` against PostgreSQL 16 to confirm clean extension compilation and installation.
3. **UUIDv7 Validation**: Executes `SELECT uuid_generate_v7();` to confirm custom PostgreSQL function availability.
4. **Laravel PostgreSQL Migration Smoke**: Runs `php artisan migrate --force` and `migrate:status` against the disposable PostgreSQL container.

> [!NOTE]
> This job is an **Infrastructure Smoke Test**, NOT a full PostgreSQL integration test suite. Normal API unit and feature tests run against SQLite in the `api` job.

---

## 7. Mapping CI Checks to Local Commands

Every CI check maps directly to a local development command:

| CI Job | CI Step | Local PowerShell Command | Local Bash Command |
|--------|---------|--------------------------|--------------------|
| **Repository** | Compose check | `docker compose config` | `docker compose config` |
| | Script check | N/A (exercised on Linux) | `bash -n scripts/*.sh` |
| **API** | Tests | `cd apps/api; php artisan test` | `cd apps/api && php artisan test` |
| | Pint | `cd apps/api; composer lint:test` | `cd apps/api && composer lint:test` |
| **Web** | Tests | `cd apps/web; npm run test` | `cd apps/web && npm run test` |
| | Lint | `cd apps/web; npm run lint` | `cd apps/web && npm run lint` |
| | Typecheck | `cd apps/web; npm run typecheck` | `cd apps/web && npm run typecheck` |
| | Build | `cd apps/web; npm run build` | `cd apps/web && npm run build` |
| **Mobile** | Format | `cd apps/mobile; dart format --output=none --set-exit-if-changed .` | `cd apps/mobile && dart format --output=none --set-exit-if-changed .` |
| | Tests | `cd apps/mobile; flutter test` | `cd apps/mobile && flutter test` |
| | Analyze | `cd apps/mobile; flutter analyze` | `cd apps/mobile && flutter analyze` |
| **Desktop** | Build | `dotnet build apps/desktop/FlowOS.sln -c Release` | `dotnet build apps/desktop/FlowOS.sln -c Release` |
| | Tests | `dotnet test apps/desktop/FlowOS.sln -c Release` | `dotnet test apps/desktop/FlowOS.sln -c Release` |
| | Format | `dotnet format apps/desktop/FlowOS.sln --verify-no-changes` | `dotnet format apps/desktop/FlowOS.sln --verify-no-changes` |
| **All** | Aggregate Tests | `.\scripts\dev.ps1 test` | `./scripts/dev.sh test` |

---

## 8. Troubleshooting CI Failures

1. **Docker Compose Configuration Error**:
   Run `docker compose config` locally to inspect YAML syntax and variable interpolation.
2. **PHP / Laravel Test Failure**:
   Check if the SQLite database is properly created in-memory during tests (`phpunit.xml`). Run `php artisan test --filter=<TestName>`.
3. **Pint Formatting Failure**:
   Run `composer lint` (or `./vendor/bin/pint`) in `apps/api` to apply formatting locally, then commit.
4. **Next.js Build Failure**:
   Run `npm run build` in `apps/web`. Common causes include missing environment variables or type mismatches in page routes.
5. **Flutter Formatting Failure**:
   Run `dart format .` in `apps/mobile` to format Dart code according to standards.
6. **.NET Desktop Build Failure on Linux**:
   Desktop CI requires Windows (`windows-latest`) due to WPF dependencies. Ensure desktop checks are not targeted to Linux runners.
