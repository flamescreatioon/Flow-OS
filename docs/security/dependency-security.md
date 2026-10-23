# Dependency Security

Security updates receive priority; compatible patch/minor updates are preferred, while major upgrades require deliberate review. Dependabot is configured weekly for npm, Composer, NuGet, pub, and GitHub Actions. CI now adds CodeQL analysis for JavaScript/TypeScript and C# only; it does not claim PHP or Dart coverage.

The framework lines are current and supported: the API runs on Laravel 12.70.0 and the web client on Next.js 15.5.28 with React 19. `composer audit` reports no advisories and `npm audit --omit=dev` reports zero vulnerabilities for the production dependency tree. The remaining `npm audit` findings are development-tooling transitive dependencies (`sprintf-js` and `braces`) whose published latest versions are themselves within the affected ranges, so no upstream fix exists; they are not shipped to production and are tracked as development-only debt.

GitHub Actions presently use major tags. Immutable commit-SHA pinning is the preferred future supply-chain hardening step and should be reviewed as a coordinated workflow change. Audits must be rerun when dependencies change; findings in the current audit are a point-in-time record.
