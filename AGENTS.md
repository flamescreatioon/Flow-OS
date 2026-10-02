# AGENTS.md

This file guides AI coding agents working on flowOS.

## 1. Product Overview

**flowOS** is an offline-capable, multi-tenant SaaS business operating system designed initially for Nigerian businesses.

Future modules include:

- Inventory
- POS
- Sales
- Purchasing
- Invoicing
- Accounting
- Expenses
- Payments
- Customers
- Suppliers
- Notes
- Tasks
- Reporting
- Analytics
- Staff management
- Branch management

Agents must not assume that all modules already exist.

---

## 2. Technology Stack

```text
Web        Next.js + TypeScript
API        Laravel + PHP
Database   PostgreSQL
Cache      Redis
Mobile     Flutter
Desktop    C# / .NET
```

Architecture: **Modular monolith**

Do not introduce microservices without an explicit architecture decision.

---

## 3. Architecture Principles

### Domain Layering
```
Domain Layer (Pure Business Logic)
    ↑
Application Layer (Use Cases)
    ↑
Infrastructure Layer (Technical Implementation)
    ↑
HTTP/UI Layer (Controllers, Components)
```

Business rules MUST live in Domain/Application layers, NOT in:
- Controllers
- UI Components
- Database migrations
- API Resources

### Controller Responsibility
Controllers should only:
- Parse HTTP requests
- Call Application layer (Commands/Queries)
- Return HTTP responses

No business logic in controllers.

### UI Component Responsibility
UI components (React, Flutter, WPF) should only handle:
- Presentation logic
- User interactions
- State management (UI state)

Business calculations, validations, workflows → Application/Domain layer.

---

## 4. Coding Rules

1. **Inspect existing code before modifying it.**
2. **Read relevant documentation before architectural changes.**
3. **Never overwrite useful work without reason.**
4. **Never modify unrelated files.**
5. **Keep changes scoped to the current task.**
6. **Follow existing naming conventions.**
7. **Prefer simple solutions over unnecessary abstraction.**
8. **Do not add dependencies unless necessary.**
9. **Do not introduce new frameworks without explicit approval.**
10. **Do not introduce microservices.**
11. **Do not introduce distributed messaging systems unless explicitly approved.**
12. **Do not expose secrets.**
13. **Do not commit `.env`.**
14. **Do not use floating-point values for authoritative money.** See `docs/finance/README.md`.
15. **Use UTC instants for `*_at`, DATE for calendar dates, IANA timezone IDs, and never client timestamps for sync ordering.** See `docs/datetime/README.md`.
16. **Tenant-owned data must remain organization-scoped.**
17. **Offline behavior must be considered for relevant business operations.**
18. **APIs must remain backward-compatible unless explicitly approved otherwise.**
19. **Important business rules require tests.**
20. **Database migrations should be reversible where practical.**
21. **Never claim tests passed unless they were actually run.**
22. **Never claim something was verified if it was not verified.**
23. **Update documentation when architectural behavior changes.**
24. **Redis is not authoritative business storage.** See `docs/redis/README.md`.
25. **Tenant cache keys must include organization context.** See `docs/redis/tenant-cache-isolation.md`.
26. **Do not make `/api/v1/health` depend on Redis.**
27. **Do not use Redis locks instead of DB constraints.**
28. **Queue jobs must establish and clear tenant context.**
29. **Core local infrastructure uses Docker Compose.** See `docs/development/docker.md`.
30. **PostgreSQL and Redis run in Docker.** Application runtimes normally run natively during development. See `docs/development/local-setup.md`.
31. **Do not add infrastructure services casually.** Keep Compose minimal (PostgreSQL, Redis, Mailpit).
32. **Laravel migrations are the application schema evolution mechanism.** Infrastructure SQL is limited to foundational database extensions/functions.
33. **Do not delete development volumes without explicit intent.** Distinguish schema reset (`php artisan migrate:fresh`) from volume deletion (`docker compose down -v`). See `docs/development/database-reset.md`.
34. **Do not weaken lint, type, formatter, or analysis settings to make failures disappear.**
35. **Do not introduce new warnings or broad suppressions; document verified pre-existing debt separately.**
36. **Do not leave placeholder, commented-out, or production debug code.**
37. **Run the relevant quality checks before completion and report their actual outcome.** See `docs/engineering/code-quality.md`.

---

## 5. Database Rules

- PostgreSQL is the source of truth for cloud data.
- Prefer UUIDv7-style globally unique IDs for business records.
- Tenant-owned records require `organization_id` scoping.
- Money must not use floating-point storage.
- Use integer minor units (kobo for NGN, cents for USD).
- Use exact representations: BIGINT minor units or field-specific NUMERIC(p,s)/DECIMAL(p,s).
  No universal NUMERIC(19,4) rule. See `docs/finance/README.md` and `docs/database/money.md`.
- C# `decimal` / Dart `int` (minor units).
- Every migration must have a working `down()` method.
- Test rollback: `php artisan migrate:rollback --step=1`

---

## 6. Multi-Tenancy Rules

- All tenant-owned data MUST be filtered by `organization_id`.
- Laravel: Global scopes on models.
- SQL: `WHERE organization_id = ?`
- API: Automatic via middleware.
- Never trust client-provided `organization_id`.

---

## 7. Offline-First Rules

- New functionality must account for offline behavior:
  - Local writes first (optimistic)
  - Queue for sync
  - Conflict resolution strategy documented
  - Test offline scenarios
- Offline-safe features must be designed around:
  ```
  Local database
  → application
  → sync layer
  → API
  → PostgreSQL
  ```
- Agents must not build features that assume constant internet connectivity where the feature is expected to work offline.

---

## 8. Financial Data Rules

- **Never** use `float`, `double`, or `number` for monetary values.
- Use integer minor units (kobo for NGN, cents for USD).
- Use exact representations: BIGINT minor units or field-specific NUMERIC(p,s)/DECIMAL(p,s).
  No universal NUMERIC(19,4) rule. See `docs/finance/README.md` and `docs/database/money.md`.
- C# `decimal` / Dart `int` (minor units).
- Default currency: NGN.

---

## 9. API Rules

- Version APIs: `/api/v1/`, `/api/v2/`
- Deprecate before removing
- Update OpenAPI spec
- Communicate in release notes
- Controllers remain thin - delegate to Application layer
- No business logic in controllers

---

## 10. Security Rules

- Never commit `.env`, keys, tokens, passwords
- Use `.env.example` for templates
- Secrets via Vault/Env vars in CI/CD
- Rotate compromised secrets immediately
- Do not log passwords or authentication tokens
- Do not print sensitive environment values
- Tenant identifiers select context only; authorization requires an active membership and permission check.
- Never mass-assign client input into tenant, role, membership, approval, or security fields.
- Client financial totals, permission snapshots, and clocks are untrusted; the server re-authorizes and recalculates.
- Browser tokens must not use localStorage/sessionStorage; mobile/desktop tokens require platform secure storage.
- Never log Authorization headers, bearer tokens, cookies, passwords, secrets, or unnecessary sensitive personal data.
- Security changes require meaningful negative-path tests for authorization, tenancy, and cleanup behavior.

---

## 11. Dependency Rules

- Do not add dependencies unless necessary.
- Do not introduce new frameworks without explicit approval.
- Evaluate alternatives before adding.
- Document the need in an ADR for significant additions.
- Prefer standard library over external dependencies.

---

## 12. Testing Rules

- Every behavior change requires relevant tests; never add meaningless tests for counts.
- Tenant, security, authorization, and financial behavior require negative-path tests.
- Normal unit tests must not depend on live external services or production data.
- Use PostgreSQL integration tests for PostgreSQL-specific behavior; SQLite is not proof of PostgreSQL correctness.
- Run relevant tests before claiming completion and never mark an unexecuted required suite as passing.
- See `docs/testing/README.md` for the testing strategy and current baseline.

---

## 13. Documentation Rules

When architectural decisions change:
- Update relevant docs in `docs/`
- Create new ADR if significant
- Update code comments if needed
- Do not update documentation for changes that did not actually occur

---

## 14. Git Rules

- Inspect `git status` before major changes
- Avoid modifying unrelated unstaged work
- Preserve user changes
- Avoid destructive Git commands
- Avoid force resets (`git reset --hard`)
- Avoid deleting branches
- Avoid rewriting history
- Do not automatically commit unless explicitly instructed
- Never use `git clean -fd` or `git push --force` without explicit permission

---

## 15. Task Execution Protocol

Every coding agent should follow:

```
1. Understand
2. Inspect
3. Plan
4. Implement
5. Verify
6. Report
```

### Understand
Read the task completely. Identify: requested outcome, scope, files likely involved, architectural implications.

### Inspect
Inspect existing: code, configuration, tests, documentation. Do not assume a file is missing before checking.

### Plan
For meaningful changes, briefly determine: files to create, files to modify, dependencies involved, tests required. Do not create unnecessary planning documents for trivial changes.

### Implement
Only modify files required by the task. Avoid opportunistic refactoring unless necessary.

### Verify
Run the appropriate: formatter, linter, type checker, unit tests, integration tests, build command as available.

### Report
Return:
```
Created
Modified
Verified
Issues
Remaining Work
```
Never hide failures.

---

## 16. Scope Control

Agents must not expand tasks beyond what was requested.

If asked to "create authentication database models," the agent should not independently:
- Redesign the frontend
- Implement POS
- Change CI
- Replace the database
- Add unrelated packages

Agents may flag related work but must not perform it unless required.

---

## 17. Verification Requirements

### Web (Next.js)
- `npm run lint`
- `npm run typecheck`
- `npm run test`
- `npm run build`

### API (Laravel)
- `composer lint` (Pint)
- `php artisan test`
- `php artisan serve` (boot check)

### Mobile (Flutter)
- `flutter analyze`
- `flutter test`

### Desktop (.NET)
- `dotnet format --verify-no-changes`
- `dotnet test`
- `dotnet build`

Agents should only run commands applicable to the current task.

### Continuous Integration (CI) Guidance
- CI configuration lives under `.github/workflows/ci.yml`.
- **Do not weaken or skip blocking checks** to merge code or make pipelines pass.
- **Local relevant verification is mandatory** before creating handoffs or claiming completion.
- **Flutter analyzer debt**: `flutter analyze` is temporarily advisory (`continue-on-error: true`) in hosted CI only. Local `dev verify` remains strictly non-zero (`exit 1`) to preserve debt visibility.
- **Workflow & check name stability**: Renaming workflow files or job names directly impacts GitHub branch protection rules and requires deliberate review.
- Reference: `docs/engineering/ci.md`

---

## 18. Multi-Agent Collaboration

When multiple agents are used:
1. Each agent should receive a narrow task.
2. Avoid assigning overlapping files simultaneously.
3. Architecture changes should be handled before implementation tasks.
4. Agents should inspect current repository state before writing.
5. Agents must not assume another agent's work has already been merged.
6. Handoff notes should document incomplete work.
7. The current repository state is authoritative.

---

## 19. Completion Reporting

Every completion report must include:
- Created
- Modified
- Verified
- Issues
- Remaining Work

Do not mark work DONE purely because files were generated. DONE means:
- Requested implementation completed
- Appropriate verification performed
- Failures reported
- Documentation updated where needed

---

## 20. Forbidden Architecture Changes

Agents must not introduce the following without explicit approval:
- Microservices
- Kubernetes
- Kafka
- RabbitMQ
- Elasticsearch
- GraphQL
- Event sourcing
- Service mesh
- CQRS frameworks
- Multiple primary databases
- Unnecessary abstraction layers

---

## 21. Architecture Decision Rule

Agents may identify architecture decisions, but significant changes require an ADR.

Require an ADR for decisions involving:
- New framework
- New database
- New application architecture
- Sync strategy changes
- Tenancy model changes
- Authentication architecture changes
- API paradigm changes
- New infrastructure platform

Minor implementation details do not need ADRs.

---

## 22. Existing Code Preservation

Before modifying a file:
1. Read it.
2. Understand its purpose.
3. Preserve existing functionality where possible.
4. Avoid deleting code simply because another structure is preferred.

Agents should prefer incremental improvement over wholesale replacement.

---

## 23. Agent Boundaries

Conceptual ownership:
```
Web work          → apps/web
Backend work      → apps/api
Mobile work       → apps/mobile
Desktop work      → apps/desktop
Shared contracts  → packages/contracts
Infrastructure    → infrastructure
Architecture/docs → docs
```
Cross-cutting tasks may modify multiple areas but should state why.

---

## 24. Project Structure Reference

```
flowos/
├── apps/
│   ├── web/          # Next.js
│   ├── api/          # Laravel
│   ├── mobile/       # Flutter
│   └── desktop/      # .NET
├── packages/
│   ├── contracts/    # OpenAPI specs
│   ├── config/       # Shared config
│   ├── design-tokens/# Design tokens
│   └── types/        # TypeScript types
├── infrastructure/   # Docker, DB, nginx
├── docs/             # All documentation
├── scripts/          # Build/dev scripts
└── .github/          # CI/CD
```

---

## 25. Key Files Reference

| File | Purpose |
|------|---------|
| `docker-compose.yml` | Local infrastructure |
| `apps/web/package.json` | Web dependencies |
| `apps/api/composer.json` | API dependencies |
| `apps/mobile/pubspec.yaml` | Mobile dependencies |
| `apps/desktop/FlowOS.sln` | Desktop solution |
| `packages/contracts/openapi/v1.yaml` | API contract |
| `docs/adr/*.md` | Architecture decisions |

---

## 26. Common Commands

```bash
# Start infrastructure
./scripts/dev.sh up

# Web
cd apps/web && npm run dev

# API
cd apps/api && php artisan serve

# Mobile
cd apps/mobile && flutter run

# Desktop
cd apps/desktop && dotnet run --project src/FlowOS.Desktop

# Tests
./scripts/dev.sh test

# Verification
./scripts/dev.sh verify

# Database
./scripts/dev.sh db-migrate
./scripts/dev.sh db-reset
```

PowerShell equivalents:
```powershell
.\scripts\dev.ps1 up
.\scripts\dev.ps1 test
.\scripts\dev.ps1 verify
```

---

---

## 27. Environment Variables

| Variable | Description |
|----------|-------------|
| `NEXT_PUBLIC_API_URL` | API base URL for web |
| `DB_HOST` | PostgreSQL host |
| `DB_DATABASE` | Database name |
| `REDIS_HOST` | Redis host |
| `APP_KEY` | Laravel encryption key |

---

## 28. Getting Help

- Check `docs/` for architecture decisions
- Check `docs/adr/` for historical context
- Look at existing code for patterns
- Ask in PR discussions
- Detailed rules in `docs/engineering/`

---

## 29. Development Scripts

Use existing scripts for standard repository-wide development operations:

```text
./scripts/dev.sh setup
./scripts/dev.sh up
./scripts/dev.sh test
./scripts/dev.sh lint
./scripts/dev.sh verify
./scripts/dev.sh db-migrate
./scripts/dev.sh db-reset
```

Do not duplicate script functionality without reason.

Scripts must be non-destructive by default:
- `docker compose down` preserves volumes (no `-v`)
- `db-reset` refuses `APP_ENV=production` and non-local `DB_HOST`
- `db-reset` requires explicit `RESET` confirmation
- `.env` files are never overwritten

Verification scripts must return correct exit codes for CI compatibility.

Reference: `docs/development/scripts.md`

---

## 30. Observability Rules

- Never log secrets, auth headers, cookies, tokens, passwords, or full request/response bodies by default.
- Prefer structured logs with stable lowercase dot-separated event names and safe contextual IDs.
- Use request/correlation IDs; log tenant IDs only from validated tenant context.
- Logs are not business or audit truth. Durable audit events require their own append-oriented design.
- Do not introduce external telemetry, log-shipping, or observability infrastructure without architecture review.
- Do not silence errors merely to reduce log noise; classify and diagnose them safely.
- See `docs/observability/README.md`.
