# Dependency Policy

## Principles

1. **Minimize dependencies** - Each dependency adds risk
2. **Prefer standard library** - Use built-in before external
3. **Audit regularly** - Check for vulnerabilities monthly
4. **Pin versions** - Exact versions in lockfiles
5. **Justify additions** - Document why each is needed

## Approval Process

### New Dependency Request
```markdown
## Dependency Request

**Package**: `package-name`
**Version**: `^1.2.3`
**Ecosystem**: npm / composer / pub / nuget
**Purpose**: What problem does this solve?
**Alternatives considered**:
- Built-in solution?
- Lighter alternative?
- Copy-paste code?
**License**: MIT / Apache-2.0 / BSD-3 / etc.
**Bundle size impact**: (for frontend)
**Maintenance status**: Active / Deprecated / Unmaintained
**Security audit**: `npm audit` / `composer audit` / `flutter pub outdated` / `dotnet list package --vulnerable`
```

### Approval Levels
| Risk Level | Approval Required |
|------------|-------------------|
| Standard library / Dev dependency | Self |
| Well-known, active (express, lodash, carbon) | Team lead |
| New, niche, or large | Architect + Security review |
| GPL/AGPL licensed | Legal review |

## Ecosystem Guidelines

### npm (Web)
```json
// package.json - exact versions
{
  "dependencies": {
    "react": "18.3.0",
    "next": "14.2.0"
  },
  "devDependencies": {
    "typescript": "5.4.0",
    "jest": "29.7.0"
  }
}

// Rules
// - No `^` or `~` in dependencies (use exact)
// - Peer dependencies respected
// - Audit: `npm audit` in CI
// - Update: `npm outdated` monthly
```

### Composer (API)
```json
// composer.json - flexible but constrained
{
  "require": {
    "laravel/framework": "^11.0",
    "spatie/laravel-multitenancy": "^3.0"
  }
}

// Rules
// - Use `^` for semantic versioning
// - `composer update` monthly
// - Audit: `composer audit` in CI
// - Prefer Laravel-first packages
```

### Pub (Mobile)
```yaml
# pubspec.yaml - version ranges
dependencies:
  flutter_riverpod: ^2.5.0
  dio: ^5.4.0
  freezed_annotation: ^2.4.0

# Rules
# - Use `^` for semantic versioning
# - `flutter pub upgrade` monthly
# - Audit: `flutter pub outdated` in CI
# - Prefer official Flutter packages
```

### NuGet (Desktop)
```xml
<!-- .csproj - version ranges -->
<PackageReference Include="MediatR" Version="12.2.0" />
<PackageReference Include="FluentValidation" Version="11.9.0" />

<!-- Rules -->
<!-- - Exact versions for runtime packages -->
<!-- - `dotnet list package --outdated` monthly -->
<!-- - Audit: `dotnet list package --vulnerable` in CI -->
<!-- - Prefer Microsoft/official packages -->
```

## Allowed Licenses

| License | Status | Notes |
|---------|--------|-------|
| MIT | ✅ | Preferred |
| Apache-2.0 | ✅ | Good |
| BSD-3-Clause | ✅ | Good |
| BSD-2-Clause | ✅ | Good |
| ISC | ✅ | Good |
| MPL-2.0 | ⚠️ | Review required |
| LGPL-2.1 | ⚠️ | Dynamic linking only |
| GPL-3.0 | ❌ | Not allowed |
| AGPL-3.0 | ❌ | Not allowed |
| Custom/Proprietary | ❌ | Not allowed without legal |

## Prohibited Patterns

| Pattern | Reason |
|---------|--------|
| `left-pad` style micro-packages | Supply chain risk |
| Unmaintained packages (>2 years) | Security risk |
| Packages with known vulnerabilities | Security risk |
| GPL/AGPL dependencies | License contamination |
| Multiple packages for same purpose | Bundle size, complexity |
| Alpha/beta/rc in production | Stability risk |

## Dependency Categories

### Core (Audit Required)
| Category | Examples | Update Frequency |
|----------|----------|------------------|
| Framework | Laravel, Next.js, Flutter, .NET | Quarterly |
| Database | EF Core, Eloquent, sqflite | Quarterly |
| Auth | Sanctum, NextAuth, Riverpod auth | Quarterly |

### Feature (Audit Required)
| Category | Examples | Update Frequency |
|----------|----------|------------------|
| Payments | Stripe, Paystack, Flutterwave | As needed |
| Charts | Chart.js, Recharts, fl_chart | Semi-annual |
| PDF | DomPDF, PDFSharp, pdf | Semi-annual |

### Utility (Low Risk)
| Category | Examples | Update Frequency |
|----------|----------|------------------|
| Date | date-fns, Carbon, intl | Semi-annual |
| Validation | Zod, FluentValidation | Semi-annual |
| Utilities | clsx, lodash (specific), collection | Annual |

### Dev Dependencies (Low Risk)
| Category | Examples | Update Frequency |
|----------|----------|------------------|
| Testing | Jest, Pest, xUnit, mocktail | Semi-annual |
| Linting | ESLint, Pint, dart analyze | Semi-annual |
| Build | Vite, Webpack, build_runner | Quarterly |
| Types | @types/*, PHPStan | Semi-annual |

## Update Process

### Monthly Routine
```bash
# Web
cd apps/web && npm outdated && npm audit

# API
cd apps/api && composer outdated && composer audit

# Mobile
cd apps/mobile && flutter pub outdated

# Desktop
cd apps/desktop && dotnet list package --outdated && dotnet list package --vulnerable
```

### Update Procedure
1. Check changelog for breaking changes
2. Update in feature branch
3. Run full test suite
4. Verify no bundle size regression (web)
5. Create PR with dependency diff
6. Merge after approval

## Security Monitoring

### Automated
- **GitHub Dependabot** - Weekly PRs for updates
- **npm audit / composer audit** - In CI pipeline
- **Snyk / OWASP Dependency Check** - Monthly scan

### Manual
- **CVE monitoring** - Subscribe to security advisories
- **Changelog review** - For major version updates
- **License compliance** - Annual audit

## Lockfiles (Commit These)

| Ecosystem | Lockfile | Purpose |
|-----------|----------|---------|
| npm | `package-lock.json` | Exact versions |
| Composer | `composer.lock` | Exact versions |
| Pub | `pubspec.lock` | Exact versions |
| NuGet | `packages.lock.json` | Exact versions |

## Bundle Size Budgets (Web)

| Metric | Budget | Tool |
|--------|--------|------|
| Total JS | < 200 KB gzipped | `next build` |
| First Load JS | < 150 KB gzipped | `next build` |
| Largest chunk | < 50 KB gzipped | `@next/bundle-analyzer` |

```bash
# Analyze
ANALYZE=true npm run build
```

## Vendor Directory (Don't Commit)

```
vendor/           # Composer
node_modules/     # npm
.dart_tool/       # Pub
bin/ obj/         # NuGet
.packages         # Pub
```

## Exception Process

For exceptional cases requiring prohibited patterns:

1. Document business justification
2. Security review
3. Legal review (for licenses)
4. Architect approval
5. Mitigation plan documented
6. Review at next quarterly audit
