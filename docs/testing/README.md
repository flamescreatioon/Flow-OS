# Testing Foundation

flowOS tests protect behavior and architectural boundaries, not implementation trivia or coverage counts. This index is the testing entry point and complements [engineering quality standards](../engineering/code-quality.md).

| Topic | Standard |
|---|---|
| Strategy and layers | [testing-strategy.md](testing-strategy.md), [test-pyramid.md](test-pyramid.md) |
| Platforms | [api](api-testing.md), [web](web-testing.md), [mobile](mobile-testing.md), [desktop](desktop-testing.md) |
| Infrastructure and architecture | [database](database-testing.md), [tenancy](tenancy-testing.md), [offline](offline-testing.md) |
| Correctness-critical data | [financial](financial-testing.md), [time](time-testing.md) |
| Test data and names | [fixtures/factories](fixtures-and-factories.md), [test naming](test-naming.md) |
| Current status | [current testing audit](current-testing-audit.md) |

Run `dev test` for the normal fast suites. PostgreSQL and Redis integration are deliberately separate from normal tests and require a dedicated local test database/service; they are not configured in Sprint 0.19.

## Continuous Integration (CI)

Automated testing in GitHub Actions executes on all pull requests and pushes to `main`. See [CI Documentation](../engineering/ci.md) for details on jobs, triggers, and runner environments:
- **API**: Runs fast SQLite-based unit and feature tests (`php artisan test`).
- **Web**: Runs Jest financial unit tests (`npm run test`).
- **Mobile**: Runs Flutter unit and widget tests (`flutter test`).
- **Desktop**: Runs .NET test runner (`dotnet test`) across all desktop test projects.
- **Infrastructure Smoke**: Tests clean PostgreSQL 16 migration (`php artisan migrate --force`) and Redis ping.
