# ADR 0020: Continuous Integration Foundation

## Status

Accepted

## Decision

flowOS adopts a centralized **GitHub Actions CI architecture** encapsulated within a single `.github/workflows/ci.yml` workflow, composed of parallel, platform-native jobs that verify pull requests and pushes to `main`:

1. **GitHub Actions as CI Platform**: Native integration with repository hosting, pull requests, and future branch protection checks.
2. **Parallel, Independent Jobs**: The pipeline is structured into six independent jobs (`Repository`, `API`, `Web`, `Mobile`, `Desktop`, `Infrastructure`). Failure in one subsystem does not prevent other jobs from executing and reporting results.
3. **Platform-Native Runners**: Ubuntu runners (`ubuntu-latest`) are used for Linux-friendly workloads (Repository, API, Web, Mobile, Infrastructure), while Windows runners (`windows-latest`) are utilized for the .NET desktop client to compile and test WPF components natively.
4. **Strict Lockfile Dependencies**: All dependency installations in CI strictly consume committed lockfiles without dynamic upgrades (`composer install --no-interaction`, `npm ci`, `flutter pub get`, `dotnet restore`).
5. **Zero Production Secrets**: CI workflows require no repository secrets or cloud credentials. All validation runs against in-memory mock databases (SQLite) or ephemeral local service containers.
6. **Infrastructure Smoke Scope**: The `Infrastructure` job verifies container health (`postgres:16-alpine`, `redis:7-alpine`), extension installation (`01-extensions.sql`), UUIDv7 generation, and Laravel PostgreSQL migrations. It is strictly classified as an infrastructure smoke test, not an application integration test suite.
7. **Temporary Quality Debt Exception**: Due to 185 pre-existing Flutter analyzer findings, `flutter analyze` runs with `continue-on-error: true` in hosted CI to keep pull requests unblocked while surfacing findings visibly. This exception will be removed once the baseline is remediated to zero warnings. Local `dev verify` remains strict (`exit 1`).
8. **Required Status Check Stability**: Job names are explicitly standardized (`Repository`, `API`, `Web`, `Mobile`, `Desktop`, `Infrastructure`) to provide stable identifiers for GitHub branch protection rules.
9. **Exclusion of Deployment & Release Automation**: Cloud deployments, package publishing, and release tagging are intentionally excluded from the CI foundation.

## Consequences

- Pull requests receive rapid, parallel feedback across all four application stacks and monorepo configurations.
- Commit history on `main` is protected from regressions in tests, linting, type safety, and builds.
- Status checks can be directly enforced in GitHub repository settings once remote publishing is established.
- Local verification commands mirror CI steps precisely, allowing developers to reproduce failures locally.
- Future debt remediation sprints have a clear metric and exit criterion for making the Flutter analyzer a blocking check.
