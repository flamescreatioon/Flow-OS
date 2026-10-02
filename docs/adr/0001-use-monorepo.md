# ADR 0001: Use Monorepo

## Title
Use Monorepo for flowOS

## Status
Accepted

## Context
flowOS consists of multiple applications (web, API, mobile, desktop) that share:
- TypeScript types and interfaces
- Design tokens and UI components
- Configuration
- Business logic concepts
- Documentation

We need a repository strategy that enables:
- Atomic commits across applications
- Shared code without publishing packages
- Simplified dependency management
- Easier refactoring across boundaries

## Decision
Use a **monorepo** with the following structure:

```
flowos/
├── apps/
│   ├── web/          # Next.js
│   ├── api/          # Laravel
│   ├── mobile/       # Flutter
│   └── desktop/      # .NET
├── packages/
│   ├── contracts/    # OpenAPI specs
│   ├── config/       # Shared config
│   ├── design-tokens/# Design tokens
│   └── types/        # TypeScript types
├── infrastructure/   # Docker, DB, nginx
├── docs/             # All documentation
├── scripts/          # Build/dev scripts
└── .github/          # CI/CD
```

## Consequences

### Positive
- **Atomic changes**: Single PR can update API contract and all clients
- **Shared code**: Types, configs, design tokens shared without publishing
- **Simplified CI**: One pipeline, shared tooling
- **Easy refactoring**: Cross-app changes in one commit
- **Unified versioning**: Single version for entire system

### Negative
- **Tooling complexity**: Need monorepo-aware tools (turborepo, nx, or custom)
- **Build times**: Can grow large (mitigated by selective builds)
- **Access control**: All developers see all code
- **Merge conflicts**: More frequent on shared files

### Mitigations
- Use selective build tools (turborepo, nx)
- Path-based CI triggers
- Code owners for sensitive areas
- Clear package boundaries

## Alternatives Considered

### Multi-repo (Polyrepo)
- Separate repos for web, api, mobile, desktop, shared
- **Pros**: Independent deployments, clear ownership, smaller clones
- **Cons**: Version synchronization hell, duplicate code, cross-repo refactoring pain

### Hybrid (Core + Apps)
- Core packages published to private registry
- Apps consume published packages
- **Pros**: Clear boundaries, independent versioning
- **Cons**: Publish overhead, version lag, local development friction

## References
- [Monorepo vs Polyrepo](https://monorepo.tools/)
- [Google's Monorepo](https://cacm.acm.org/magazines/2016/7/204032-why-google-stores-billions-of-lines-of-code-in-a-single-repository/)