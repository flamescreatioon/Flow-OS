# Current Code Quality Audit

*Sprint 0.18 final, recorded 2026-09-29. This is a factual snapshot, not a claim that all repositories are warning-free.*

| Area | Status | Evidence / debt |
|---|---|---|
| API | PASS | `php artisan test`: 11 tests, 28 assertions; `pint --test`: PASS. PHPStan/Larastan/Psalm are not configured. |
| Web | PASS | `npm run lint`, `npm run typecheck`, and the non-interactive Jest suite are configured. See the current testing audit for Sprint 0.19 results. |
| Mobile | PARTIAL | `flutter test`: 2 tests PASS. `flutter analyze`: 185 pre-existing style/info warnings (no new regressions); `avoid_print` eliminated. Money conversion helpers in documentation are safe (display-only); no unsafe `double` helpers in source code. |
| Desktop | PASS | `dotnet build`: PASS (pre-existing NU1903/NU1901 advisories). `dotnet test`: 2 tests PASS. `dotnet format --verify-no-changes`: PASS (exit code 0). Known NuGet advisories documented. |
| SQL | PASS | Existing Laravel migrations use focused tables, explicit organization foreign keys/indexes where applicable, and `down()` methods. |
| Scripts | PASS | `dev help`, `docker compose config` PASS. `dev verify` executes all intended checks and correctly fails with non-zero exit code when Flutter analyzer debt is detected. |
| Dependencies | DEBT | No new dependencies added. Desktop advisories: `System.Text.Json` 8.0.0 (high: GHSA-8g4q-xg66-9fp4, GHSA-hh2w-p6rv-4g7w), `Moq` 4.20.0 (low: GHSA-6r78-m64m-qwcf), plus transitive high advisories. PHP static analysis not configured. |

## Search findings

- `TODO`: two Android template/signing TODOs; they are platform configuration work, not production placeholders.
- `FIXME` / `HACK`: no handwritten-code findings in the audited application/source paths.
- TypeScript suppressions and broad lint suppressions: none found.
- Debug-output findings: documentation-only `console.log` examples removed. Five mobile production `print` calls in API/sync paths — two in `api_client.dart` (guarded by `kDebugMode`, **KEPT**); two in `sync_engine.dart` (unconditionally printed, **REPLACED** with `debugPrint` gated by `kDebugMode`). No production `print` calls remain outside debug gating.
- Placeholder `Class1.cs`: none found. No generated Dart or designer C# output was found in the current source tree.
- Mobile `dynamic` use is concentrated at JSON/network boundaries and the API client; it is technical debt to narrow over time, not permission to add further unvalidated dynamic flow.
- Financial safety: Source code contains no unsafe `double`/`number` helpers for authoritative money. Documentation examples updated: Dart `toMajor()` renamed to `toMajorForDisplay()`, PHP `minorToMajor()` renamed to `minorToMajorForDisplay()`, both with explicit "DISPLAY PURPOSES ONLY" docblocks. TypeScript `formatMoney` is clearly for display only. No generally usable helper remains that encourages authoritative floating-point money.

## Safety baseline

Current Laravel tenancy tests pass, including membership-based tenant resolution and the unauthenticated health path. Existing finance, date-time, offline, and API standards remain the source of truth; this audit found no new contrary implementation in the limited foundation code. Offline architecture is documented as future foundation rather than implemented production sync endpoints.

## Deferred work

1. Complete and remediate the Flutter analyzer baseline in a focused mobile-quality change (Sprint 0.19+).
2. Assess safe approved-version updates for the recorded `System.Text.Json` (high) and `Moq` (low) advisories (Sprint 0.22).
3. Adopt/configure PHP static analysis in a separately scoped change.
5. Add or configure meaningful desktop tests for Application/Infrastructure layers (Sprint 0.19+).
