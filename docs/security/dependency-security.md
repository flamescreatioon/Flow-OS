# Dependency Security

Security updates receive priority; compatible patch/minor updates are preferred, while major upgrades require deliberate review. Dependabot is configured weekly for npm, Composer, NuGet, pub, and GitHub Actions. CI now adds CodeQL analysis for JavaScript/TypeScript and C# only; it does not claim PHP or Dart coverage.

GitHub Actions presently use major tags. Immutable commit-SHA pinning is the preferred future supply-chain hardening step and should be reviewed as a coordinated workflow change. Audits must be rerun when dependencies change; findings in the current audit are a point-in-time record.
