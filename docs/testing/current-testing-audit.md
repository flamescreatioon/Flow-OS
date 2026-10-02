# Current Testing Audit

*Sprint 0.19 baseline, recorded 2026-10-01.*

| Area | Status | Evidence |
|---|---|---|
| API | IMPLEMENTED | 11 Laravel feature tests / 28 assertions pass using SQLite `:memory:` and `RefreshDatabase`. No unit tests yet because no pure domain surface exists. |
| Web | IMPLEMENTED | Jest/ts-jest/Testing Library were already installed but misconfigured for non-watch execution. Sprint 0.19 configures non-interactive tests and adds 8 exact money-parser cases. |
| Mobile | PARTIAL | One configuration unit test and one bootstrap widget test are meaningful. No `integration_test/` directory. Analyzer debt remains separate. |
| Desktop | PARTIAL | All three xUnit projects are in the solution and configured correctly. The two scaffold-only domain tests and `Class1` were removed; all projects are intentionally empty until real behavior exists. |
| PostgreSQL integration | NOT CONFIGURED | No dedicated `flowos_test` suite/environment exists. Docker Desktop is unavailable in this audit environment. |
| Redis integration | NOT CONFIGURED | Normal Laravel tests use array cache and sync queue; no Redis-specific behavior exists to test. Docker Desktop is unavailable. |
| E2E | NOT CONFIGURED | No browser, WPF GUI, or mobile integration framework is added before real workflows exist. |

## Safety audit

Test/source searches found no production URLs, credentials, real emails/phones, or production database targets in test code. Tests use fake Laravel factory data and local framework configuration. No test waits/sleeps or live network calls were found in the active test suites.

## Known debt

- Flutter analyzer reports 185 pre-existing issues; it is not weakened for this sprint.
- PostgreSQL and Redis integration remain a future, safety-gated test environment.
- Desktop test projects are intentionally empty after removal of meaningless scaffolding.
