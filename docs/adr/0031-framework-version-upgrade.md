# ADR 0031: Framework Version Upgrade for Production Support

Status: ACCEPTED
Date: 2026-10-20

## Context

The API runs on Laravel 10 and the web client on Next.js 14. Both lines have reached the end of
their upstream security support window. The repository already records this as a production
blocker: `README.md` states that Laravel must reach a supported Laravel 12+ line and Next.js a
patched 15.5.24+ line before production, and `docs/security/dependency-security.md` requires
deliberate review for major upgrades.

The supported-version requirement is not cosmetic. It determines whether framework security
patches continue to be published, and it constrains the package versions the platform can adopt
before Sprint 2 business modules are built on top of them.

The upgrade is deliberately separated from the concurrent contract, tenancy, and idempotency
hardening work. Those changes alter behaviour that the framework upgrade must not be blamed for,
and bundling them would make a regression in either impossible to attribute.

## Decision

Upgrade the API from Laravel 10 to Laravel 12 and the web client from Next.js 14 to Next.js 15.

The API upgrade is performed as one major step rather than 10 to 11 to 12, because Laravel 11 is
itself out of support and stepping through it would produce two full upgrade cycles and two
verification passes for an intermediate state that is never released. Laravel 12 is installed on
PHP `^8.2`, which is the version already pinned in CI.

The framework-coupled dependencies move with the framework:

- `laravel/sanctum` to `^4.0` for the Laravel 11+ middleware and guard changes.
- `spatie/laravel-multitenancy` to `^4.0` for Laravel 11+ support.
- `nunomaduro/collision` to `^8.0`, which is the first release compatible with Laravel 11+.

The web client moves to Next.js 15 with React 19, because the App Router used by `apps/web`
requires React 19 on Next.js 15.

The application keeps its existing architecture while the frameworks move: the modular monolith,
shared-schema tenancy, Sanctum session and token authentication, membership-based authorization,
and the `packages/contracts` OpenAPI authority are all unchanged. The Laravel HTTP kernel stays
explicit and testable, and the tenancy and authorization invariants are unchanged and continue to
be verified by the existing negative-path tests.

Framework upgrades are performed on a branch, one framework at a time, with the full verification
set run and its actual output recorded before the next framework is started.

## Alternatives Considered

- Stay on Laravel 10 and Next.js 14 and accept the risk: rejected. Unsupported framework lines stop
  receiving security patches, and the gap is already recorded as a production blocker.
- Upgrade Laravel only, deferring Next.js: rejected. The two blockers are independent and both
  block production. Deferring one leaves the platform unshippable.
- Step Laravel 10 to 11 then 11 to 12: rejected. Laravel 11 is out of support, so it would add a
  full cycle of verification for a state that is never released. The Laravel 12 upgrade guide
  already documents the 10 to 11 differences that still apply.
- Replace the frameworks: rejected. No framework defect requires replacing Laravel, Sanctum, or
  Next.js. The work required is version migration, not a rewrite, and a rewrite would break the
  delivered auth, tenancy, branch, and RBAC behaviour for no stated benefit.
- Bundle the upgrade with the hardening changes: rejected. It would make any regression
  unattributable between framework behaviour and application behaviour.

## Consequences

### Positive

- The platform returns to a line that receives upstream security patches.
- The dependency set is consistent with a single Laravel major, so Sanctum and multitenancy are
  supported rather than tolerated.
- Package upgrades for later sprints are no longer blocked by an unsupported base.
- The OpenAPI contract is unaffected, so no consumer-visible API change is introduced.

### Negative / Trade-offs

- Laravel 11 removed the deprecated `casts()` model method in favour of the `casts` property, and
  changed console and HTTP kernel registration. Application code must be updated to the current
  conventions.
- `spatie/laravel-multitenancy` v4 is a major version with tenant-model and configuration changes.
  The tenancy adapter code is touched by this upgrade.
- Next.js 15 changes request and response handling in the App Router, including asynchronous
  request APIs and default caching behaviour. Web client code must be updated accordingly.
- The upgrade costs a full verification pass on both frameworks before the change is shippable.

## Security / Tenancy Impact

No authentication, authorization, or tenancy behaviour changes. Sanctum remains the authentication
mechanism for browser sessions and personal access tokens, membership remains the authorization
relationship, and the tenant selector remains distinct from tenant authorization. Existing
negative-path tests for tenancy, authorization, mass assignment, and cross-organization access are
the regression gate for this change and must pass unchanged.

The upgrade does raise the platform's minimum supported dependency versions, which narrows the set
of upstream advisories that can affect the deployed platform.

## Offline / Data Impact

No schema or migration change. The upgrade alters application framework code only, and the data
model, identifier strategy, and synchronization protocol recorded in ADR 0012 are unaffected. The
existing reversible migrations remain the schema authority.

## References

- Laravel 12 upgrade guide: <https://laravel.com/docs/12.x/upgrade>
- Laravel 11 upgrade guide (differences that still apply): <https://laravel.com/docs/11.x/upgrade>
- Next.js 15 upgrade guide: <https://nextjs.org/docs/app/guides/upgrading/version-15>
- `README.md` production support requirement
- `docs/security/dependency-security.md`
- ADR 0002 (Next.js), ADR 0003 (Laravel), ADR 0023 (tenancy and authorization), ADR 0026
  (authentication foundation)

## Implementation Outcome (2026-10-20)

The migration was applied on branch `feature/framework-upgrade` and verified.

- API: Laravel 10.50.3 to 12.70.0, Sanctum 3.3.3 to 4.3.3, spatie/laravel-multitenancy 3.2.0 to 4.2.1, PHPUnit 10.5 to 11.5, PHPUnit 8-compatible collision to 8.9.5. `composer audit` reports no advisories.
- Web: Next.js 14.2 to 15.5.28, React 18.3 to 19.3, Jest 29 to 30. `npm audit --omit=dev` reports zero vulnerabilities for the production tree.
- `composer.json` now pins `config.platform.php` to `8.2.0` so the lock file resolves for the PHP version CI uses, rather than for whichever PHP the upgrade was run on.
- Sanctum 4 removed `Sanctum::ignoreMigrations()`. The application owns the personal access token schema through its own migration, so the call was removed rather than replaced.
- spatie/laravel-multitenancy v4 renamed its `actions` configuration keys. The application configuration was rewritten against the v4 schema, because the v3 keys were being silently ignored and only appeared to work through the package defaults. Queue tenant awareness is now explicitly disabled by default so that a job dispatched outside an organization context is not silently deleted, and tenant-aware jobs opt in through the package contract.
- The Next.js lint command moved from `next lint` to the ESLint CLI because `next lint` is deprecated and removed in Next.js 16. The configuration, plugin, and linted scope are unchanged.
- `postcss` and `postcss-selector-parser` are pinned through npm `overrides` because Next 15.5.28 still depends on versions below their patched releases. The override was verified by comparing the production CSS bundle before and after; the output is byte-identical.

Two committed development artifacts were removed because they were not repository content: a debug script that printed a configuration value, and a stale SQLite database file holding a pre-hardening schema. The SQLite connection now reads its own `DB_SQLITE_DATABASE` variable so that the PostgreSQL database name can never resolve to a file beside the application.

Verification recorded for this change: Pint passes, the SQLite suite passes with 148 tests, the PostgreSQL suite passes with 149 tests, the three newest migrations are reversible on PostgreSQL, the API boots and serves the health endpoint, and the web lint, typecheck, test, and build checks all pass.

## Supersedes / Superseded By

Extends ADR 0002 and ADR 0003 with version support decisions. It does not change the framework
choices recorded there, and it does not alter ADR 0023 or ADR 0026.
