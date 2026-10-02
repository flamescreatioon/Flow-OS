# Git Workflow

This document defines the Git branching model, commit conventions, and repository collaboration rules for **flowOS**.

---

## 1. Branching Strategy

flowOS uses a simplified, **`main`-based trunk-leaning branching model**. All work originates from `main` and integrates back into `main` via reviewed pull requests.

```text
       feature/inventory-stock
      ┌───────────────────────┐
main ─┴───────────────────────┴─► [Squash & Merge]
```

### 1.1 Protected Integration Branch
- **`main`**: The single authoritative source of truth. Always kept buildable, deployable, and passing tests. Direct pushes to `main` are strictly prohibited once branch protection is active.

### 1.2 Supporting Branch Types
All feature, bug fix, chore, and documentation work happens on short-lived branches created from `main`:

| Branch Prefix | Purpose | Example |
|---------------|---------|---------|
| `feature/` | New functionality or capabilities | `feature/inventory-stock-movements` |
| `fix/` | Defect corrections and bug fixes | `fix/tenant-membership-check` |
| `chore/` | Maintenance, dependencies, tooling | `chore/testing-foundation` |
| `docs/` | Documentation, ADR updates | `docs/api-standards` |
| `refactor/` | Code structure improvements without behavior change | `refactor/extract-money-vo` |

> [!NOTE]
> Do **NOT** use GitFlow (`develop`, `release/*`, `hotfix/*`). The repository uses short-lived feature branches integrating directly into `main`.

### 1.3 Branch Naming Rules
- Use lowercase alphanumeric characters and hyphens (`kebab-case`).
- Include the descriptive prefix followed by a concise summary.
- **Avoid**: generic names (`test`, `new`, `wip`, `final`) or author-specific names (`nelson-branch`).

---

## 2. Commit Conventions

flowOS follows the **Conventional Commits** specification. Commit messages must be clear, imperative, and structured.

### 2.1 Format
```text
<type>(<scope>): <concise imperative summary>

[optional body]

[optional footer(s)]
```

### 2.2 Approved Types
| Type | Description |
|------|-------------|
| `feat` | New feature or capability |
| `fix` | Bug fix or defect correction |
| `docs` | Documentation changes only |
| `test` | Adding or correcting tests |
| `refactor` | Code restructuring without feature addition or bug fix |
| `perf` | Performance improvement |
| `build` | Changes affecting build tooling or external dependencies |
| `ci` | CI configuration and scripts (Sprint 0.21+) |
| `chore` | Repository maintenance, tasks, configuration updates |
| `style` | Formatting, whitespace changes (no production logic change) |
| `revert` | Reverting a previous commit |

### 2.3 Approved Scopes
Scopes represent subsystems or application boundaries:
- `api` — Laravel backend (`apps/api`)
- `web` — Next.js frontend (`apps/web`)
- `mobile` — Flutter mobile application (`apps/mobile`)
- `desktop` — .NET desktop application (`apps/desktop`)
- `contracts` — Shared OpenAPI contracts (`packages/contracts`)
- `types` — Monorepo shared types (`packages/types`)
- `infra` — Docker, PostgreSQL, Redis, infrastructure orchestration
- `docs` — Architecture and engineering documentation
- `repo` — Root repository configuration, scripts, licenses, templates
- `domain` — Specific business domains when useful (`inventory`, `finance`, `tenancy`, `auth`, `sync`)

### 2.4 Commit Quality Standards
- Each commit should be coherent, reviewable, and buildable where practical.
- Avoid commits like `"fix stuff"`, `"wip"`, `"updates"`.
- Do not mix unrelated functional changes in a single commit.

---

## 3. Pull Request & Merge Workflow

### 3.1 Normal Lifecycle
```text
branch → commit → push → pull request → review/checks → squash-merge → delete branch
```

### 3.2 Merge Strategy: Squash and Merge
- **Squash and Merge** is the mandatory default merge strategy for pull requests into `main`.
- **Benefits**:
  - Keeps `main` commit history clean, linear, and bisectable.
  - The entire PR becomes a single atomic commit with the PR title and description.
  - Eliminates messy intermediate WIP or fixup commits from shared history.
- Regular merge commits and rebase-merges are disabled on `main`.

### 3.3 PR Titles
Pull request titles must follow the Conventional Commit format (e.g. `feat(inventory): add stock movement aggregate`). Because squash-merge uses the PR title as the commit summary on `main`, a clean PR title ensures an immaculate repository history.

---

## 4. Synchronization, Rebase, and Force Push Policy

### 4.1 Keeping Branches Up-to-Date
When updating a local feature branch from `main`:
```bash
git fetch origin
git rebase origin/main
```
Or use `git merge origin/main` if collaborating with another team member on the same branch.

### 4.2 Force Push Policy
- Routine `git push --force` is **strictly prohibited**.
- If history rewriting on a local feature branch is necessary after an interactive rebase, use:
  ```bash
  git push --force-with-lease
  ```
  and only on private, unshared feature branches.
- Never force-push to `main` under any circumstances.

---

## 5. Conflict Resolution Protocol

When merge conflicts arise:
1. **Semantic Inspection**: Inspect conflicting changes carefully. Understand both sides of the change.
2. **Never Blindly Choose**: Never resolve conflicts by blindly picking `theirs` or `ours`.
3. **Re-verify Everything**: After conflict resolution, execute the full local verification suite before committing:
   - Build all affected projects.
   - Run linters and typecheckers.
   - Run relevant unit and integration test suites.
   - Inspect `git diff` to ensure no unintended alterations occurred.

---

## 6. Monorepo Working Tree & Tools

### Optional Git Worktrees for Parallel Tasks
For engineers or agents working on separate features concurrently (e.g. API backend vs. Flutter mobile), Git worktrees provide isolated directories without multiple repository clones:
```bash
git worktree add ../flowos-mobile feature/mobile-sync
# Work in ../flowos-mobile
git worktree remove ../flowos-mobile
```
Worktrees are an optional convenience, not a mandatory requirement.