# Code Quality Standards

This is the quality baseline for flowOS. It applies to handwritten production and test code in every application. It complements the architecture, finance, date-time, tenancy, offline, and API standards; those documents remain authoritative for their subjects.

## Quality gate

Code is acceptable to merge only when the checks relevant to the changed component pass, or a pre-existing failure is recorded with evidence. New work must have: a successful relevant build, tests, formatter check, lint/type/static checks available in that project, no unexplained warnings or suppressions, no secrets or debug artifacts, and documentation for material behavior changes.

Review also confirms organization scoping and authorization, exact money values, safe temporal semantics, offline/sync correctness where relevant, established API envelopes, error handling, and basic accessibility for web/mobile UI. Do not weaken a check to make it pass.

## Verification matrix

| Platform | Format | Analysis | Build / tests |
|---|---|---|---|
| API | `./vendor/bin/pint --test` | Pint; PHP static analysis when configured | `php artisan test` |
| Web | repository-selected tooling (no formatter is configured today) | `npm run lint`, `npm run typecheck` | `npm run build`, `npm run test` |
| Mobile | `dart format --output=none --set-exit-if-changed .` | `flutter analyze` | `flutter test` |
| Desktop | `dotnet format FlowOS.sln --verify-no-changes` | compiler and nullable analysis | `dotnet build FlowOS.sln`, `dotnet test FlowOS.sln` |
| Scripts | root `.editorconfig` | safe error handling and review | `dev help`, `dev verify` where practical |

`scripts/dev.sh verify` and `scripts/dev.ps1 verify` remain the aggregate developer-facing check. They must report failures honestly; they do not convert known debt into a pass.

The root `.editorconfig` defines UTF-8, LF, final-newline, whitespace, and indentation defaults. `.gitattributes` also normalizes repository text files to LF; do not mass-rewrite existing files solely to normalize line endings.

## Engineering expectations

Prefer readable, explicit, cohesive code that another engineer can safely modify later. Use language-appropriate types, names, and source organization. Avoid dead code, accidental duplication, unexplained magic values, deeply nested flow, giant controllers/components/classes, and abstractions without a demonstrated need. Refactor for cohesion and testability, not an arbitrary line count.

Comments explain why, constraints, or non-obvious trade-offs; they do not narrate obvious statements. Shared/public contracts receive documentation when behavior is not otherwise clear. TODOs must be specific and must not hide a security or data-integrity gap.

New core logic must be testable without live network services, production Redis, global mutable state, or uncontrolled wall-clock time where a clock boundary is warranted. Generated output is governed by [generated-code.md](generated-code.md), dependencies by [dependency-quality.md](dependency-quality.md), and warnings by [warnings-and-suppressions.md](warnings-and-suppressions.md).

Testing layers, infrastructure boundaries, deterministic data, and platform commands are defined in [Testing Foundation](../testing/README.md).

## Code review checklist

- [ ] Relevant build, tests, format, lint/type/static checks ran and their outcome is recorded.
- [ ] No new warnings, broad suppressions, secrets, placeholder code, or production debug output.
- [ ] Names, types, error handling, dependencies, and comments make intent clear.
- [ ] Tenant data is organization-scoped; authorization does not trust an ID merely because it was supplied.
- [ ] Money is exact; time, API, and offline behavior follow their established standards.
- [ ] Migrations are PostgreSQL-correct, deliberate, reversible where practical, and indexed/constraint-aware.
- [ ] Documentation and tests cover material behavior; introduced TODOs are reported at handoff.

## Platform standards

- [PHP / Laravel](php-quality.md)
- [TypeScript / Next.js](typescript-quality.md)
- [Flutter / Dart](flutter-quality.md)
- [.NET / C#](dotnet-quality.md)
- [SQL / migrations](sql-quality.md)
- [Shell / PowerShell](script-quality.md)
- [Naming conventions](naming-conventions.md)
- [Current audit](current-code-quality-audit.md)
