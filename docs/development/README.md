# flowOS Development Documentation

Welcome to the flowOS developer documentation. This directory is the canonical onboarding entrypoint for software engineers and coding agents working on the codebase.

---

## 1. Quick Navigation

| Document | Purpose |
|----------|---------|
| [Prerequisites](prerequisites.md) | Required runtimes, toolchain versions, and OS compatibility |
| [Local Setup Guide](local-setup.md) | Step-by-step first-time setup and daily development workflow |
| [Environment Configuration](environment.md) | Environment variables, `.env` file management, host vs container networking, secrets |
| [Docker Infrastructure](docker.md) | Local Compose topology, service specifications, volumes, and port management |
| [Infrastructure Services](infrastructure-services.md) | Deep specification for PostgreSQL, Redis, Mailpit, and Docker networks |
| [Database Workflow](database.md) | Schema authority (Laravel migrations), PostgreSQL extensions, migration commands, and reset safety |
| [Database Reset Guide](database-reset.md) | Application schema reset vs full Docker volume reset workflows |
| [Platform Development Guide](platform-development.md) | Per-application guides for Web (Next.js), API (Laravel), Mobile (Flutter), Desktop (.NET) |
| [Development Scripts](scripts.md) | Reference for `dev.ps1` and `dev.sh` automation scripts |
| [Troubleshooting Guide](troubleshooting.md) | Diagnosing port conflicts, Docker issues, build artifact locks, and request IDs |
| [Current Development Audit](current-development-audit.md) | Status audit of developer-facing documentation and technical debt |

---

## 2. Fast Happy Path (Zero to Running)

For developers with prerequisites installed:

```powershell
# 1. Setup dependencies and environment configuration
.\scripts\dev.ps1 setup

# 2. Start PostgreSQL, Redis, and Mailpit
.\scripts\dev.ps1 up

# 3. Apply database migrations
.\scripts\dev.ps1 db-migrate

# 4. Verify test suites across all platforms
.\scripts\dev.ps1 test
```
*(On Linux / macOS, use `./scripts/dev.sh <command>`)*.

To run individual applications natively:
- **API**: `cd apps/api && php artisan serve` (`http://localhost:8000`)
- **Web**: `cd apps/web && npm run dev` (`http://localhost:3000`)
- **Mobile**: `cd apps/mobile && flutter run`
- **Desktop**: `cd apps/desktop && dotnet run --project src/FlowOS.Desktop/FlowOS.Desktop.csproj` (Windows only)

---

## 3. Local Verification Baseline & Technical Debt

When executing repository verification checks, observe the following baseline:

| Verification Suite | Target Command | Expected Outcome | Notes |
|--------------------|----------------|------------------|-------|
| **Functional Tests** | `dev test` | **PASS (exit 0)** | Runs API, Web, Mobile, and Desktop test suites |
| **API Code Style** | `pint --test` | **PASS (exit 0)** | Formatted per flowOS PHP standards |
| **Web Typecheck** | `npm run typecheck` | **PASS (exit 0)** | Zero TypeScript compiler errors |
| **Web Lint** | `npm run lint` | **PASS (exit 0)** | Zero ESLint errors |
| **Web Build** | `npm run build` | **PASS (exit 0)** | Next.js production build succeeds |
| **Desktop Build/Test** | `dotnet test` | **PASS (exit 0)** | Solution builds cleanly (zero test cases discovered) |
| **Mobile Analyzer** | `flutter analyze` | **186 findings** | Accepted baseline technical debt |
| **Aggregate Verification** | `dev verify` | **FAIL (exit 1)** | Exits with code 1 **strictly due to Flutter analyzer debt** |

### Known Pre-Existing Technical Debt:
- **Flutter Analyzer Debt**: 186 accepted findings in `apps/mobile`. In hosted CI, `flutter analyze` is temporarily advisory (`continue-on-error: true`). In local development, `dev verify` exits with code 1 to maintain visibility into this debt.
- **Framework Security Upgrades**:
  - Laravel 10 reaches end-of-life and requires controlled migration to Laravel 12+.
  - Next.js 14.2 requires migration to `>=15.5.24` prior to production release.
- **Client Synchronization Prototypes**: Mobile `SyncEngine` and desktop SQLite persistence are foundational prototypes and will be refined during the synchronization milestone.
- **Remote GitHub Repository**: Git remote is currently **not configured**. Local commits must be made on feature branches; do not attempt `git push origin`.

---

## 4. Canonical Hierarchy & Guardrails

- **Architecture Authority**: System boundaries, module plans, and multi-tenancy rules live in [Architecture Documentation](../architecture/README.md).
- **Decision History**: Accepted architecture decisions live in [ADRs](../adr/).
- **Security Guardrails**: Authentication, tenant isolation, and credential handling rules live in [Security Documentation](../security/README.md).
- **Observability Standards**: Request ID tracing, structured logging, and metric rules live in [Observability Documentation](../observability/README.md).
- **Testing Philosophy**: Test pyramids and testing environments live in [Testing Strategy](../testing/README.md).
- **Contribution Workflow**: Branching, Conventional Commits, and PR review standards live in [CONTRIBUTING.md](../../CONTRIBUTING.md) and [Git Workflow](../engineering/git-workflow.md).
