# Config Package

Shared configuration constants for all flowOS applications.

## Purpose

This package contains shared configuration values that are used across multiple applications in the flowOS monorepo. It serves as a single source of truth for configuration values that need to be consistent across:

- Web (Next.js)
- Mobile (Flutter)
- Desktop (.NET)
- API (Laravel)

## Contents

- `config` - Shared configuration object with:
  - App metadata (name, version, description)
  - API versioning and base path
  - Currency defaults and supported currencies
  - Date/time defaults (timezone, locale)
  - Pagination defaults
  - ID strategy (UUIDv7)
  - Multi-tenancy configuration

## Usage

```typescript
import { config } from '@flowos/config';

console.log(config.app.name); // 'flowOS'
console.log(config.currency.default); // 'NGN'
console.log(config.api.basePath); // '/api/v1'
```

## Design Principles

1. **No secrets** - This package never contains secrets or environment-specific values
2. **Type-safe** - Uses `as const` for maximum TypeScript inference
3. **Single source of truth** - Applications should not duplicate these values
4. **Cross-platform awareness** - Values are chosen to be portable across TypeScript, PHP, Dart, and C#

## What NOT to Put Here

- Environment-specific values (use `.env` files)
- Secrets or credentials
- Feature flags that vary by deployment
- Runtime-computed values

## Versioning

Configuration changes follow the same versioning as the monorepo. Breaking changes to the config shape require a major version bump.

## Future Considerations

When workspace tooling (pnpm/npm workspaces, Turborepo, Nx) is introduced, this package may be published as an internal package `@flowos/config`.