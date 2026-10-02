# Current Scripts Audit

*Sprint 0.17 — Development Scripts Audit*

## Overview

This audit assesses the state of development automation scripts in flowOS, covering setup, infrastructure management, testing, linting, formatting, verification, and database operations.

## Audit Matrix

| Category | Script | Status | Details |
|----------|--------|--------|---------|
| **Setup** | `scripts/setup.sh` | `IMPLEMENTED` | Cross-platform prerequisites, env file copy (no overwrite), dependency installation, Laravel key generation **only if missing** |
| **Setup** | `scripts/setup.ps1` | `IMPLEMENTED` | PowerShell equivalent with same safety guarantees |
| **Infrastructure** | `dev.sh up/down/status/logs` | `IMPLEMENTED` | Thin wrappers around `docker compose` |
| **Infrastructure** | `dev.ps1 up/down/status/logs` | `IMPLEMENTED` | PowerShell equivalent |
| **Tests** | `dev.sh test` / `dev.ps1 test` | `IMPLEMENTED` | Runs API, Web, Mobile, Desktop tests where tooling available |
| **Lint** | `dev.sh lint` / `dev.ps1 lint` | `IMPLEMENTED` | API (Pint), Web (ESLint), Mobile (flutter analyze) |
| **Format** | `dev.sh format/format-check` / `dev.ps1 format/format-check` | `IMPLEMENTED` | API (Pint), Flutter (dart format), Desktop (dotnet format) |
| **Verification** | `dev.sh verify` / `dev.ps1 verify` | `IMPLEMENTED` | Repository-wide: Docker config, API tests+Pint, Web lint+typecheck, Mobile tests+analyze, Desktop build+tests+format |
| **Database** | `dev.sh db-migrate/db-status/db-reset` | `IMPLEMENTED` | Laravel migrations, status, destructive reset with safety |
| **Database** | `dev.ps1 db-migrate/db-status/db-reset` | `IMPLEMENTED` | PowerShell equivalent with safety protections |
| **Help** | `dev.sh help` / `dev.ps1 help` | `IMPLEMENTED` | Usage documentation |

## Safety Assessment

| Operation | Safety Status | Protections |
|-----------|---------------|-------------|
| `.env` file creation | `SAFE` | Only copies when destination missing; never overwrites |
| Infrastructure `down` | `SAFE` | Uses `docker compose down` (preserves volumes) |
| `db-reset` | `SAFE` | Blocks `APP_ENV=production`, non-local `DB_HOST`, requires `RESET` confirmation |
| `db-migrate` | `SAFE` | Standard Laravel migration |
| `docker compose down -v` | `NOT IMPLEMENTED` | Intentionally excluded from normal workflow |

## Cross-Platform Alignment

| Command | Bash | PowerShell | Aligned |
|---------|------|------------|---------|
| setup | ✅ | ✅ | Yes |
| up | ✅ | ✅ | Yes |
| down | ✅ | ✅ | Yes |
| status | ✅ | ✅ | Yes |
| logs | ✅ | ✅ | Yes |
| test | ✅ | ✅ | Yes |
| lint | ✅ | ✅ | Yes |
| format | ✅ | ✅ | Yes |
| format-check | ✅ | ✅ | Yes |
| verify | ✅ | ✅ | Yes |
| db-migrate | ✅ | ✅ | Yes |
| db-status | ✅ | ✅ | Yes |
| db-reset | ✅ | ✅ | Yes |
| help | ✅ | ✅ | Yes |

## Known Issues

| Issue | Classification | Mitigation |
|-------|----------------|------------|
| Flutter analyzer pre-existing warnings | `KNOWN DEBT` | Documented; verify fails but does not block legitimate development |
| Docker daemon unavailable | `ENVIRONMENT` | Clear error message with remediation guidance |
| Missing tooling (flutter/dotnet/npm/php) | `SAFETY` | `verify` fails if required tool missing; other commands skip gracefully with warning |

## Regression Verification

| Component | Verification Method |
|-----------|---------------------|
| API tests | `php artisan test` — PASS |
| API Pint | `./vendor/bin/pint --test` — PASS |
| Web lint | `npm run lint` — PASS |
| Web typecheck | `npm run typecheck` — PASS |
| Flutter tests | `flutter test` — PASS |
| Desktop build | `dotnet build` — PASS |
| Desktop tests | `dotnet test` — PASS |
| Docker Compose config | `docker compose config` — PASS |

## Remaining Work

| Item | Priority | Notes |
|------|----------|-------|
| `doctor` command | Low | Optional diagnostic command for tool versions |
| CI integration | Sprint 0.21 | Scripts designed for future CI reuse |
| Windows-only path handling | Low | PowerShell handles paths natively |
| Parallel test execution | Low | Currently sequential; acceptable for current scale |

## Summary

All mandatory Sprint 0.17 acceptance criteria satisfied:
- ✅ Primary Bash and PowerShell developer workflows exist
- ✅ Script semantics are aligned
- ✅ Setup behavior is safe (no overwrites, **preserves existing APP_KEY**)
- ✅ Infrastructure start/stop/status/logs commands exist
- ✅ Tests/lint/format/verify commands exist and consistent
- ✅ Destructive DB reset is protected (production, non-local DB, confirmation)
- ✅ `.env` files never overwritten
- ✅ **Setup is idempotent** — preserves existing environment files and APP_KEY
- ✅ Scripts return meaningful exit codes
- ✅ Scripts work from repository root
- ✅ Docker daemon failures handled clearly
- ✅ Modern `docker compose` syntax used
- ✅ Documentation exists (`docs/development/scripts.md`)
- ✅ Current script audit exists (this document)
- ✅ AGENTS.md updated with script guidance
- ✅ No CI/release/deploy scripts added
- ✅ No new task-runner framework introduced
- ✅ No business modules implemented
- ✅ No regressions in existing app verification
- ✅ **Verify fails if required tools missing** (php, npm, flutter, dotnet)
