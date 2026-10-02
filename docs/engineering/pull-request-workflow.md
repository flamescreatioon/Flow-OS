# Pull Request Workflow & Review Policy

This document defines the lifecycle, review expectations, and merge standards for all pull requests in **flowOS**.

---

## 1. Pull Request Lifecycle

Every code change must progress through the following standardized workflow:

```text
Branch creation (from main)
       ↓
Implementation & Local Testing
       ↓
Local Verification (lint, format, test)
       ↓
Conventional Commit(s)
       ↓
Push to remote feature branch
       ↓
Open Pull Request (using PR template)
       ↓
Code Review & CI Status Checks
       ↓
Resolve Feedback & Push Updates
       ↓
Squash and Merge into main
       ↓
Delete Head Branch
```

### Detailed Steps

1. **Create/Update Branch**
   Always branch from up-to-date `main`:
   ```bash
   git checkout main
   git pull origin main
   git checkout -b feature/your-feature-name
   ```
2. **Implement Changes**
   Scope changes strictly to the assigned task. Avoid opportunistic or unrelated refactoring.
3. **Verify Locally**
   Run the relevant quality and test commands before opening the PR:
   ```powershell
   .\scripts\dev.ps1 test
   ```
4. **Commit Locally**
   Use Conventional Commits (`feat(scope): ...`, `fix(scope): ...`).
5. **Push Branch**
   Push to the remote repository:
   ```bash
   git push -u origin feature/your-feature-name
   ```
6. **Open Pull Request**
   Fill out `.github/pull_request_template.md` completely. Document actual verification commands and outcomes.
7. **Review & Discussion**
   Address reviewer questions and requested changes promptly.
8. **Resolve Feedback**
   Push subsequent commits to the same branch; GitHub will update the PR automatically.
9. **Squash and Merge**
   Once approved and all checks pass, squash and merge into `main`. The PR title becomes the commit message.
10. **Delete Branch**
    Delete the remote feature branch upon merge to maintain repository hygiene.

---

## 2. Review Size & Coherence

- **Reviewable Size**: PRs should be focused and coherent. Ideal PRs address a single feature, bug fix, or architectural step.
- **Atomic Architectural Changes**: Avoid arbitrarily splitting a coherent, atomic architectural change (e.g. schema migration + model + contract) into unusable fragments that break intermediate builds.
- **Unrelated Changes**: Do not mix unrelated formatting, refactoring, or dependency bumps into business logic PRs.

---

## 3. Draft Pull Requests

Draft PRs are encouraged for:
- Early architectural visibility and alignment before extensive code is written
- Agent handoffs and shift transitions across development sessions
- Work-in-progress feedback on complex cross-cutting tasks

> [!IMPORTANT]
> Draft PRs must never be merged until marked "Ready for review", verified locally, and approved.

---

## 4. Verification & Testing Standards

PR authors must document exact test executions and results in the PR description:
```text
- `php artisan test` — PASS (36 tests, 88 assertions)
- `npm run test` — PASS (4 tests, 4 passed)
- `npm run lint` — PASS
- `npm run typecheck` — PASS
- `flutter test` — PASS (2 tests passed)
- `dotnet test` — PASS (0 tests executed)
```
Generic claims such as `"tested"` or `"it works"` are not acceptable.

### Reporting Known Debt
PRs must transparently disclose any known debt or pre-existing failures in the designated PR template section:
- Known existing failures (e.g. Flutter analyzer pre-existing findings)
- Newly deferred debt or edge cases tracked in subsequent issues
- Environment limitations (e.g. local Docker vs. native runtime differences)

---

## 5. High-Risk Review Areas

Pull requests involving the following subsystems carry elevated risk and require explicit domain review:

| Domain | Review Focus | Mandatory Checks |
|--------|--------------|------------------|
| **Multi-Tenancy** | Isolation across organizations | Verify global scopes, `organization_id` filters, middleware enforcement. Reject client-provided tenant IDs. |
| **Authentication & Authorization** | Access control & membership | Role validation, session handling, token security, privilege escalation prevention. |
| **Financial Integrity** | Authoritative calculations | Strict integer minor units (kobo/cents), exact decimal precision, rounding rules, zero floating-point arithmetic. |
| **Database Migrations** | Schema evolution & data safety | Working, reversible `down()` method; non-blocking DDL where practical; lock times evaluated. |
| **Offline Sync** | Outbox and sync engine | Idempotent mutations, conflict resolution policies, device cursor tracking. |
| **Security & Secrets** | Data protection | Zero credentials committed, sensitive fields masked in logging and responses. |

> [!CAUTION]
> High-risk PRs (security, tenancy, financial integrity) should not be self-approved if a second reviewer or lead maintainer is available.

---

## 6. Recommended Label Taxonomy

A lightweight, standardized set of labels should be used to organize PRs and issues:

### Type Labels
- `type:bug` — Bug fix or defect correction
- `type:feature` — New functionality or user-facing enhancement
- `type:tech-task` — Engineering task, refactor, or test improvement
- `type:tech-debt` — Known debt remediation or cleanup
- `type:docs` — Documentation or ADR update

### Area Labels
- `area:api` — Laravel backend (`apps/api`)
- `area:web` — Next.js frontend (`apps/web`)
- `area:mobile` — Flutter mobile application (`apps/mobile`)
- `area:desktop` — .NET desktop application (`apps/desktop`)
- `area:contracts` — OpenAPI contracts (`packages/contracts`)
- `area:infra` — Infrastructure, Docker, DB, Redis (`infrastructure/`)

### Domain & Priority Labels
- `domain:security` — Authentication, authorization, secrets, permissions
- `domain:tenancy` — Multi-tenant data isolation and membership
- `domain:finance` — Money handling, transactions, accounting, pricing
- `domain:offline` — Local storage, sync protocol, idempotency
- `priority:high` — Critical path or blocking issue
