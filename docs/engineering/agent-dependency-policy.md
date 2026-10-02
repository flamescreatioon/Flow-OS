# Agent Dependency Policy

Rules for adding, updating, and managing dependencies across all flowOS applications.

---

## Core Principle

**Minimize dependencies.** Each dependency adds maintenance burden, security surface, and build complexity.

A dependency may be added only if:
1. The standard library/framework does not reasonably solve the problem
2. The dependency is actively maintained (releases within last 12 months)
3. It materially reduces complexity or development time
4. It does not duplicate an existing dependency
5. License is compatible (MIT, Apache-2.0, BSD-3-Clause preferred)

---

## Approval Process

### Minor Dependencies (utility libraries, dev tools)
- Self-approved by implementing agent
- Must be documented in completion report

### Major Dependencies (frameworks, ORMs, UI libraries, payment SDKs)
- Requires ADR documenting:
  - Problem being solved
  - Alternatives evaluated
  - Why this specific package
  - License verification
  - Bundle size impact (for frontend)
  - Maintenance status

### Prohibited Without Exception
- GPL/AGPL licensed packages
- Unmaintained packages (no releases > 18 months)
- Packages with known critical vulnerabilities
- Multiple packages serving the same purpose

---

## Ecosystem-Specific Guidelines

### Web (Next.js/TypeScript) - npm
```json
{
  "dependencies": {
    "exact-version": "1.2.3"  // Prefer exact versions
  },
  "devDependencies": {
    "typescript": "5.4.0"
  }
}
```
- Use exact versions (`1.2.3`) not ranges (`^1.2.3`)
- Audit monthly: `npm audit`
- Update: `npm outdated` → PR with changelog review

### API (Laravel/PHP) - Composer
```json
{
  "require": {
    "spatie/laravel-multitenancy": "^3.0"
  }
}
```
- Use caret ranges (`^3.0`) for semantic versioning
- Audit monthly: `composer audit`
- Update: `composer outdated` → PR with changelog review

### Mobile (Flutter/Dart) - pub
```yaml
dependencies:
  flutter_riverpod: ^2.5.0
  dio: ^5.4.0
```
- Use caret ranges for Dart packages
- Audit: `flutter pub outdated`
- Generate lockfile: `pubspec.lock` committed

### Desktop (.NET/C#) - NuGet
```xml
<PackageReference Include="MediatR" Version="12.2.0" />
```
- Exact versions for runtime packages
- Audit: `dotnet list package --vulnerable`
- Update: `dotnet list package --outdated` → PR with changelog review

---

## License Compliance

| License | Status | Notes |
|---------|--------|-------|
| MIT | ✅ Preferred | |
| Apache-2.0 | ✅ Good | |
| BSD-3-Clause | ✅ Good | |
| BSD-2-Clause | ✅ Good | |
| ISC | ✅ Good | |
| MPL-2.0 | ⚠️ Review required | File-level copyleft |
| LGPL-2.1 | ⚠️ Review required | Dynamic linking only |
| GPL-3.0 | ❌ Prohibited | Viral copyleft |
| AGPL-3.0 | ❌ Prohibited | Network viral |
| Custom/Proprietary | ❌ Prohibited | Legal review required |

---

## Prohibited Patterns

| Pattern | Reason |
|---------|--------|
| Micro-packages (e.g., `left-pad`, `is-odd`) | Supply chain risk, bundle bloat |
| Unmaintained packages (>2 years no releases) | Security risk, compatibility issues |
| Multiple packages for same purpose | Bundle size, maintenance burden |
| Alpha/beta/rc in production | Stability risk |
| Git dependencies (non-tagged) | Reproducibility, security |

---

## Update Procedure

1. Check changelog for breaking changes
2. Update in feature branch
3. Run full test suite
4. Verify no bundle size regression (web)
5. Create PR with dependency diff and changelog summary
6. Merge after approval

---

## Security Monitoring

### Automated
- GitHub Dependabot - weekly PRs for updates
- `npm audit` / `composer audit` / `flutter pub outdated` / `dotnet list package --vulnerable` in CI
- Snyk / OWASP Dependency Check - monthly scan

### Manual
- CVE monitoring via security advisories
- Changelog review for major version updates
- Annual license compliance audit