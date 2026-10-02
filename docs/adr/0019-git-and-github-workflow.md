# ADR 0019: Git and GitHub Workflow

## Status

ACCEPTED

## Current Status Note

`main` is the integration branch intended for production-ready changes after hosting and branch protection are configured. This does not claim that the current application is production-ready; framework security migrations and other documented Sprint 0 debt remain blockers.

## Decision

flowOS adopts a simplified, **`main`-based trunk-leaning Git branching model** with pull request-based integration and **squash-and-merge** defaults:

1. **`main` Integration Branch**: `main` serves as the authoritative, production-ready branch. Direct feature commits and direct pushes to `main` are prohibited once repository branch protection is configured.
2. **Short-Lived Supporting Branches**: All work is developed on scoped, short-lived branches created from `main` using standardized prefixes (`feature/*`, `fix/*`, `chore/*`, `docs/*`, `refactor/*`). GitFlow constructs (`develop`, `release/*`, `hotfix/*`) are rejected as unnecessary complexity for the modular monolith at this stage.
3. **Conventional Commits**: Commit messages and pull request titles adhere to the Conventional Commits specification (`type(scope): imperative summary`) to maintain consistent, bisectable, and legible repository history.
4. **Squash and Merge Policy**: Pull requests are integrated into `main` exclusively via squash-and-merge. This collapses intermediate development/WIP commits into a single atomic change set while retaining full PR discussion and testing records in GitHub.
5. **Quality & Verification Gates**: Every pull request requires local verification (`dev test`), completed PR template checklists, domain review for high-risk areas (tenancy, authorization, finance, offline sync, migrations), and at least one code approval.
6. **CI Checks**: Formal CI enforcement on GitHub Actions is deferred to Sprint 0.21; required status check settings are documented but marked pending until CI workflows are active.
7. **No Enforced Pre-Commit Hooks**: External Git hook management frameworks (e.g. Husky, Lefthook, pre-commit) are intentionally excluded; developer scripts (`scripts/dev.ps1`, `scripts/dev.sh`) and CI pipelines provide sufficient verification without adding developer environment friction.

## Consequences

- Commit history on `main` remains clean, linear, and straightforward to inspect and bisect.
- Monorepo developers and coding agents work against a single shared baseline (`main`) without multi-branch synchronization drag.
- Merged branches are automatically deleted to prevent remote clutter.
- High-risk architectural changes (tenancy, financial arithmetic, sync idempotency) receive explicit review before entering the integration branch.
- Automated branch protection and required status checks are clearly documented for administrative setup upon repository publishing.
