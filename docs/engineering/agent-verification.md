# Agent Verification Requirements

Defines the verification commands and standards for each application in flowOS.

---

## Verification Matrix

| Application | Lint/Format | Type Check | Unit Tests | Integration Tests | Build | Boot Check |
|-------------|-------------|------------|------------|-------------------|-------|------------|
| Web (Next.js) | `npm run lint` | `npm run typecheck` | `npm run test` | `npm run test:integration` | `npm run build` | N/A |
| API (Laravel) | `composer lint` | `./vendor/bin/phpstan` | `php artisan test --filter=Unit` | `php artisan test --filter=Feature` | N/A | `php artisan serve` |
| Mobile (Flutter) | `flutter analyze` | N/A (Dart is typed) | `flutter test` | `flutter test integration_test/` | `flutter build apk --release` | N/A |
| Desktop (.NET) | `dotnet format --verify-no-changes` | N/A (compile-time) | `dotnet test --filter "Category=Unit"` | `dotnet test --filter "Category=Integration"` | `dotnet build` | `dotnet run` |

---

## Required Verification by Task Type

### New Feature
- All applicable verifications for the application
- Integration tests for new endpoints/UI flows
- Offline behavior tests (if applicable)

### Bug Fix
- Regression test for the specific bug
- Related unit/integration tests
- Build verification

### Refactor
- All existing tests pass
- No performance regression (if measurable)
- Build verification

### Documentation Only
- Link integrity check
- Markdown lint (if configured)

### Dependency Update
- Full test suite
- Bundle size check (web)
- Build verification

---

## Verification Commands Reference

### Web (Next.js)
```bash
cd apps/web

# Lint
npm run lint

# TypeScript check
npm run typecheck

# Unit tests
npm run test

# Integration tests (if configured)
npm run test:integration

# E2E tests (if configured)
npm run test:e2e

# Production build
npm run build
```

### API (Laravel)
```bash
cd apps/api

# Format check (Laravel Pint)
composer lint

# Static analysis (PHPStan)
./vendor/bin/phpstan analyse --memory-limit=512M

# Unit tests
php artisan test --filter=Unit

# Feature/Integration tests
php artisan test --filter=Feature

# All tests with coverage
php artisan test --coverage

# Application boot check
php artisan serve --port=8000 &
sleep 3
curl -f http://localhost:8000/api/v1/health
```

### Mobile (Flutter)
```bash
cd apps/mobile

# Static analysis
flutter analyze

# Unit tests
flutter test

# Integration tests
flutter test integration_test/

# Generate coverage
flutter test --coverage

# Release build verification
flutter build apk --release --split-per-abi
flutter build ios --release --no-codesign
```

### Desktop (.NET)
```bash
cd apps/desktop

# Format verification
dotnet format --verify-no-changes

# Unit tests
dotnet test FlowOS.sln --filter "Category=Unit"

# Integration tests
dotnet test FlowOS.sln --filter "Category=Integration"

# All tests with coverage
dotnet test FlowOS.sln --collect:"XPlat Code Coverage"

# Build
dotnet build FlowOS.sln --configuration Release

# Run verification
dotnet run --project src/FlowOS.Desktop
```

---

## CI Integration

These commands are executed in GitHub Actions:
- `.github/workflows/web-ci.yml`
- `.github/workflows/api-ci.yml`
- `.github/workflows/mobile-ci.yml`
- `.github/workflows/desktop-ci.yml`

Agents should ensure local verification passes before pushing.

---

## Minimum Verification Checklist

Before marking any task DONE:

| Application | Minimum Commands |
|-------------|------------------|
| Web | `npm run lint && npm run typecheck && npm run test && npm run build` |
| API | `composer lint && php artisan test` |
| Mobile | `flutter analyze && flutter test` |
| Desktop | `dotnet format --verify-no-changes && dotnet test && dotnet build` |

---

## Verification Reporting

In completion report, include:

```markdown
## Verified
- Web: `npm run lint` ✅, `npm run typecheck` ✅, `npm run test` ✅, `npm run build` ✅
- API: `composer lint` ✅, `php artisan test` ✅
- Mobile: `flutter analyze` ✅, `flutter test` ✅
- Desktop: `dotnet format --verify-no-changes` ✅, `dotnet test` ✅, `dotnet build` ✅
```

### If Verification Fails
```markdown
## Verified
- Web: `npm run lint` ❌ (ESLint error in components/ui/Button.tsx)
- API: `composer lint` ✅, `php artisan test` ❌ (2 failing tests)

## Issues
- Web: Button component has unused prop - needs fix
- API: ProductControllerTest fails due to missing validation
```