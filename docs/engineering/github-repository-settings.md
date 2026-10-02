# GitHub Repository Settings & Branch Protection Policy

This document defines the authoritative target configuration for GitHub repository settings, branch protection rules, and merge policies for **flowOS**.

---

## 1. Status: DOCUMENTED — NOT APPLIED

> [!NOTE]
> As of Sprint 0.20, these settings are **DOCUMENTED — NOT APPLIED** on the remote GitHub repository. They represent the required administrative policies that repository maintainers must configure in GitHub repository settings once the remote is linked and authorized.

---

## 2. General Repository Settings

| Setting | Recommended Value | Rationale |
|---------|-------------------|-----------|
| **Repository Name** | `flowos` | Clean, standard lowercase slug. |
| **Description** | `flowOS — Offline-capable business operating system for Nigerian businesses.` | Clear, concise product overview matching repository documentation. |
| **Default Branch** | `main` | Modern industry standard integration branch. |
| **Issues** | Enabled | Primary issue tracking via `.github/ISSUE_TEMPLATE/`. |
| **Discussions** | Disabled (Sprint 0.20) | Centralize communication in issues and PRs during early foundation. |
| **Wiki** | Disabled | All technical documentation is source-controlled under `docs/`. |
| **Projects** | Enabled / Optional | For sprint roadmap and milestone planning. |

---

## 3. Pull Request & Merge Strategy

| Setting | Value | Rationale |
|---------|-------|-----------|
| **Allow squash merging** | **Enabled (Default)** | Keeps `main` commit history clean, linear, and readable. Entire PR becomes one atomic, reviewable commit. |
| **Default squash commit message** | Pull request title and description | Preserves Conventional Commit title and PR context in git history. |
| **Allow merge commits** | Disabled (or strictly restricted) | Prevents noisy merge bubble commits in the integration branch. |
| **Allow rebase merging** | Disabled (unless explicitly justified) | Squash-and-merge is the standard for flowOS feature branches. |
| **Automatically delete head branches** | **Enabled** | Cleans up merged `feature/*`, `fix/*`, `chore/*` branches to keep remote clean. |

---

## 4. Branch Protection Rules for `main`

The `main` branch is the production-ready integration branch. The following rules must be configured in **Settings → Branches → Branch protection rules** for pattern `main`:

### 4.1 Pull Request Requirements
- [x] **Require a pull request before merging**
  - Direct pushes to `main` are strictly forbidden for all contributors and automated agents.
- [x] **Require approvals: 1 minimum**
  - All PRs must receive at least one code review approval before merge.
- [x] **Dismiss stale pull request approvals when new commits are pushed**
  - Ensures newly pushed commits undergo review.
- [x] **Require review from Code Owners** (Deferred until GitHub teams/handles are configured).
- [x] **Require approval of the most recent reviewable push**
  - Prevents race conditions where an approval is granted on outdated code.

### 4.2 Conversation Resolution
- [x] **Require conversation resolution before merging**
  - All review comments, questions, and blocker notes must be resolved or explicitly marked resolved before merge.

### 4.3 Required Status Checks
- [ ] **Require status checks to pass before merging**
  - **Status: PENDING SPRINT 0.21**
  - CI workflows and exact job check names are being finalized in Sprint 0.21. Once CI workflows run on GitHub Actions, required status checks will include:
    - Web CI (lint, typecheck, test, build)
    - API CI (lint, tests with PostgreSQL/Redis, PHPStan)
    - Mobile CI (flutter analyze, flutter test)
    - Desktop CI (dotnet format, dotnet build, dotnet test)
  - Do not invent check names prior to Sprint 0.21 workflow implementation.

### 4.4 History & Safety Controls
- [x] **Require linear history**
  - Enforces clean history compatible with squash-merge policy.
- [x] **Do not allow bypassing the above settings**
  - Applies to administrators as well, ensuring no accidental direct pushes or bypassed reviews.
- [x] **Block force pushes (`git push --force`)**
  - Prohibits force pushes to `main` completely.
- [x] **Block deletions**
  - Prohibits deleting the `main` branch.

---

## 5. Administrative Setup Checklist

When linking the remote GitHub repository, repository administrators should execute the following checklist:

1. [ ] Set default branch to `main`.
2. [ ] Add repository description: `flowOS — Offline-capable business operating system for Nigerian businesses.`
3. [ ] Set merge button defaults: Allow squash merge only; enable automatic branch deletion.
4. [ ] Create branch protection rule for `main`:
   - [ ] Require pull request before merging (1 approval).
   - [ ] Dismiss stale approvals on push.
   - [ ] Require conversation resolution.
   - [ ] Restrict force pushes and branch deletions.
5. [ ] **Pending Sprint 0.21**: Activate required CI status checks after GitHub Actions workflows are verified.
