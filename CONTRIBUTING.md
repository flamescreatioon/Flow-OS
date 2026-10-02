# Contributing to flowOS

Thank you for contributing to **flowOS** — an offline-capable, multi-tenant SaaS business operating system designed for Nigerian businesses.

This guide provides practical instructions for both human contributors and AI coding agents.

---

## 1. Getting Started & Setup

Before contributing code, set up your local development environment:
- Follow the [Local Setup Guide](docs/development/local-setup.md) to install dependencies and run native runtimes.
- Start local infrastructure (PostgreSQL, Redis, Mailpit) using Docker Compose:
  ```powershell
  # Windows PowerShell
  .\scripts\dev.ps1 up
  ```
  ```bash
  # Linux / macOS
  ./scripts/dev.sh up
  ```
- Refer to [Development Environment ADR](docs/adr/0017-docker-development-environment.md) and [Development Setup Documentation](docs/engineering/development-setup.md) for more details.

---

## 2. Branch Naming Strategy

All work is based on short-lived branches created from `main`:

```bash
git checkout main
git pull origin main
git checkout -b <prefix>/<short-description>
```

Approved branch prefixes:
- `feature/` — New functionality (`feature/inventory-stock-movements`)
- `fix/` — Bug fixes (`fix/tenant-membership-check`)
- `chore/` — Tooling, dependencies, repository maintenance (`chore/testing-foundation`)
- `docs/` — Documentation updates (`docs/api-standards`)
- `refactor/` — Code restructuring without functional alterations (`refactor/money-vo`)

See [Git Workflow Documentation](docs/engineering/git-workflow.md) for complete branching rules.

---

## 3. Commit Message Conventions

We adhere to the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```text
<type>(<scope>): <concise imperative description>
```

- **Types**: `feat`, `fix`, `docs`, `test`, `refactor`, `perf`, `build`, `ci`, `chore`, `style`, `revert`
- **Scopes**: `api`, `web`, `mobile`, `desktop`, `contracts`, `types`, `infra`, `docs`, `repo`
- **Examples**:
  - `feat(api): add branch switching endpoint`
  - `fix(web): prevent duplicate form submission on offline checkout`
  - `test(finance): add negative test cases for kobo overflow`

---

## 4. Architectural Constraints & Guardrails

All contributions must adhere to core architectural rules:

1. **Modular Monolith**: Do not introduce microservices, distributed queues (Kafka/RabbitMQ), or event sourcing frameworks without an explicit ADR decision.
2. **Domain Layering**: Business rules belong in Domain and Application layers. Controllers, UI components, and API resources must remain thin.
3. **Multi-Tenancy**: All tenant-owned data must be scoped by `organization_id`. Never trust client-supplied tenant IDs. See [Tenancy Documentation](docs/architecture/multi-tenancy.md).
4. **Financial Accuracy**: Never use floating-point types (`float`, `double`, `number`) for money. Use integer minor units (kobo for NGN, cents for USD). See [Financial Rules](docs/finance/README.md).
5. **Time Semantics**: Use UTC instants for timestamps (`*_at`), SQL `DATE` for business dates, and IANA timezone IDs for organization context. See [DateTime Guidelines](docs/datetime/README.md).
6. **Offline-First**: New client features must account for local-first persistence, optimistic writes, and sync idempotency. See [Offline Architecture](docs/offline/README.md).

---

## 5. Testing & Local Verification

Before creating a pull request, run the applicable quality and verification suites locally:

```powershell
# Run all unit and integration test suites
.\scripts\dev.ps1 test

# Run full repository verification (lint, typecheck, tests, build)
.\scripts\dev.ps1 verify
```

Equivalent bash scripts are available under `./scripts/dev.sh`.

Individual subsystem test commands:
- **API (Laravel)**: `cd apps/api && php artisan test`
- **Web (Next.js)**: `cd apps/web && npm run test`
- **Mobile (Flutter)**: `cd apps/mobile && flutter test`
- **Desktop (.NET)**: `dotnet test apps/desktop/FlowOS.sln`

---

## 6. Pull Request Process

1. Push your feature branch to GitHub:
   ```bash
   git push -u origin feature/your-feature-name
   ```
2. Open a Pull Request targeting `main`.
3. Fill out the [Pull Request Template](.github/pull_request_template.md) completely, reporting exact test results.
4. Address review comments and keep conversations resolved.
5. All PRs are merged into `main` using **Squash and Merge** to maintain a clean, linear history.

See [Pull Request Workflow](docs/engineering/pull-request-workflow.md) for full review and merge expectations.

---

## 7. Security & Secrets

- **Never Commit Secrets**: Never commit `.env` files, API keys, private certificates, or database credentials. Ensure `.env.example` contains only non-sensitive dummy values.
- **Reporting Vulnerabilities**: If you discover a security vulnerability, please report it privately to the repository maintainers rather than creating a public issue.

---

## 8. Agent-Specific Guidelines

Coding agents (Antigravity, Codex Pro, OpenCode) must adhere to [AGENTS.md](AGENTS.md) and [Agent Git Rules](docs/engineering/agent-git-rules.md). Agents may prepare changes and local commits when tasked, but must never execute remote pushes or branch merges without explicit user authorization.
