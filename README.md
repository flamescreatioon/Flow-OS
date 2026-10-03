# flowOS

flowOS is an offline-capable, multi-tenant business operating system for Nigerian businesses. It is a modular monolith foundation; Sprint 1 business modules have not started.

## Architecture

Web, mobile, and desktop clients use the Laravel API. PostgreSQL is the authoritative cloud database; Redis is temporary infrastructure for supported cache, queue, session, rate-limit, and coordination concerns. Detailed architecture and current-versus-target status live in the [architecture entrypoint](docs/architecture/README.md).

## Technology stack

| Layer | Technology |
|---|---|
| Web | Next.js 14, React, TypeScript |
| API | Laravel 10, PHP, PostgreSQL 16 |
| Mobile | Flutter, Dart, Riverpod |
| Desktop | .NET 8, WPF, C# |
| Temporary infrastructure | Redis 7 |
| Local infrastructure | Docker Compose |

Laravel must migrate to a supported Laravel 12+ line and Next.js must migrate to a patched 15.5.24+ line before production. See [dependency security](docs/security/dependency-security.md).

## Quick start

```powershell
# Automated setup (dependencies & environment)
.\scripts\dev.ps1 setup

# Start backing infrastructure & run tests
.\scripts\dev.ps1 up
.\scripts\dev.ps1 test
```

Applications normally run natively during development. Docker Compose supplies PostgreSQL, Redis, and optional Mailpit. See [development onboarding](docs/development/README.md) and [development scripts](docs/development/scripts.md).

## Repository structure

```text
apps/           Web, API, mobile, and desktop applications
packages/       OpenAPI contracts and shared packages
infrastructure/ Local infrastructure configuration
docs/           Architecture, development, engineering, security, and product documentation
scripts/        Repository development commands
```

## Documentation

- [Developer onboarding & local setup](docs/development/README.md)
- [Architecture](docs/architecture/README.md)
- [Security](docs/security/README.md)
- [Observability](docs/observability/README.md)
- [Offline architecture](docs/offline/README.md)
- [API documentation](docs/api/README.md)
- [Engineering policy & CI](docs/engineering/code-quality.md)
- [Testing strategy](docs/testing/README.md)
- [ADRs](docs/adr/)
- [Documentation index](docs/README.md)

## Contributing

Read [contribution guidelines](CONTRIBUTING.md), [developer onboarding](docs/development/README.md), and [AGENTS.md](AGENTS.md) before contributing to the repository.
