# Code Ownership & Responsibility Model

This document establishes the code ownership structure and review responsibilities for the **flowOS** monorepo.

---

## 1. Ownership Principles

In flowOS, code ownership is **responsibility-based rather than restrictive access-based**:
- **Architectural Awareness**: Owners are responsible for the coherence, patterns, domain layering, and long-term maintainability of their subsystem.
- **Review Responsibility**: Owners act as primary reviewers for changes touching their respective areas.
- **Open Contribution**: Any team member or coding agent may contribute to any module or application, provided work is submitted via a pull request that respects project standards and receives appropriate review.
- **No Invented Identities**: Automated GitHub `CODEOWNERS` enforcement is deferred until organizational GitHub teams and usernames are officially provisioned.

---

## 2. Monorepo Ownership Areas

| Area | Primary Stack | Responsibility & Review Focus |
|------|---------------|-------------------------------|
| `/apps/api` | Laravel / PHP 8.2 | Domain layering, multi-tenant isolation (`organization_id`), database migrations, authorization policies, REST API endpoints, performance, and transactional safety. |
| `/apps/web` | Next.js / TypeScript | Web application UI/UX, responsive state management, client-side caching, design system token consumption, and accessibility. |
| `/apps/mobile` | Flutter / Dart | Mobile client experience, offline-first local storage, SQLite/Hive caches, sync engine client adapters, and mobile lifecycle. |
| `/apps/desktop` | C# / .NET 8 (WPF) | Desktop application, offline-first local database, device peripherals, local hardware integration, and sync engine client adapters. |
| `/packages/contracts` | OpenAPI 3.0 / JSON Schema | Canonical source of truth for all HTTP API contracts. Changes require cross-platform review (Web, API, Mobile, Desktop). |
| `/packages/*` (config, types, tokens) | TypeScript / JSON | Shared configurations, cross-platform design tokens, and shared TypeScript types consumed by applications. |
| `/infrastructure` | Docker, Postgres, Redis, Nginx | Local development orchestration, database initialization scripts, extension setup, cache configuration, and container networks. |
| `/docs` | Markdown | Architecture Decision Records (ADRs), domain specifications (finance, datetime, offline, redis), and engineering rules. |
| `AGENTS.md` & `.github/` | Markdown / YAML | System governance, prompt constraints, collaboration rules, and GitHub issue/PR templates. |

---

## 3. High-Risk Review Areas

Certain cross-cutting subsystems carry elevated risk to business operations, tenant security, and data integrity. Pull requests touching these areas require rigorous scrutiny:

1. **Multi-Tenancy & Authorization (`/apps/api`, `/docs/security`)**
   - Must guarantee tenant boundary isolation (`organization_id` scoping).
   - Must not trust client-supplied tenant identifiers.
2. **Financial Calculations & Ledger (`/docs/finance`, `/apps/*`)**
   - Strictly prohibit floating-point money.
   - Enforce integer minor units (kobo/cents).
   - Ensure rounding and tax calculations match authoritative test vectors.
3. **Database Migrations (`/apps/api/database/migrations`)**
   - Must have working, reversible `down()` implementations.
   - Must consider table locks, indexing overhead, and rollback safety.
4. **Offline Sync & Idempotency (`/docs/offline`, `/apps/*`)**
   - Outbox event ordering, deterministic conflict resolution, and idempotent server mutations.
5. **Shared API Contracts (`/packages/contracts/openapi/v1.yaml`)**
   - Changes must remain backward-compatible with deployed mobile and desktop clients.

---

## 4. GitHub CODEOWNERS File Status

GitHub native auto-assignment via `.github/CODEOWNERS` requires verified GitHub usernames or organization teams.

Because flowOS is currently in foundational development with a core engineering setup, a `.github/CODEOWNERS` file is **intentionally omitted at this stage** to prevent invalid parser warnings or dead notifications.

Once team accounts and organization permissions are finalized on GitHub, `.github/CODEOWNERS` will be configured using the areas mapped above.
