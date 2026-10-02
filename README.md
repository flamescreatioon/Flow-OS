# flowOS

**Offline-capable Business Operating System for Nigerian Businesses**

flowOS is a modular monolith SaaS platform providing Point of Sale, Inventory, Sales, Purchasing, Invoicing, Payments, Expenses, Accounting, and Business Analytics — designed to work fully offline with seamless cloud synchronization.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        Clients                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │   Web    │  │ Mobile   │  │ Desktop  │  │  Future  │    │
│  │ (Next.js)│  │ (Flutter)│  │  (.NET)  │  │  Clients │    │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘    │
└───────┼─────────────┼─────────────┼─────────────┼───────────┘
        │             │             │             │
        └─────────────┼─────────────┼─────────────┘
                      ▼
        ┌─────────────────────────┐
        │      Laravel API        │
        │    (Modular Monolith)   │
        │  ┌───────────────────┐  │
        │  │   Domain Layer    │  │
        │  │   Application     │  │
        │  │   Infrastructure  │  │
        │  │   HTTP Layer      │  │
        │  └───────────────────┘  │
        └───────────┬─────────────┘
                    ▼
        ┌─────────────────────────┐
        │      PostgreSQL         │
        │      + Redis            │
        └─────────────────────────┘
```

## Technology Stack

| Layer | Technology |
|-------|------------|
| **Web Frontend** | Next.js 14, React 18, TypeScript, Tailwind CSS |
| **API Backend** | Laravel 11, PHP 8.2, PostgreSQL 16 |
| **Mobile** | Flutter 3, Dart 3, Riverpod |
| **Desktop** | .NET 8, WPF, C# |
| **Database** | PostgreSQL 16 |
| **Cache/Queue** | Redis 7 |
| **Infrastructure** | Docker, Docker Compose |

## Repository Structure

```
flowos/
├── apps/
│   ├── web/          # Next.js application
│   ├── api/          # Laravel API
│   ├── mobile/       # Flutter application
│   └── desktop/      # .NET WPF application
├── packages/
│   ├── contracts/    # OpenAPI specifications
│   ├── config/       # Shared configuration
│   ├── design-tokens/# Design tokens
│   └── types/        # TypeScript types
├── infrastructure/   # Docker, Database, Nginx
├── docs/             # Architecture, Engineering, API docs
├── scripts/          # Setup, Dev, Test scripts
└── .github/          # CI/CD, Issue templates
```

## Prerequisites

| Tool | Version |
|------|---------|
| Git | 2.40+ |
| Docker Desktop | 4.25+ |
| Node.js | 20 LTS |
| PHP | 8.2+ |
| Composer | 2.6+ |
| Flutter | 3.16+ |
| .NET SDK | 8.0+ |

## Quick Start

### 1. Clone and Setup

```bash
git clone https://github.com/your-org/flowos.git
cd flowos

# Linux/macOS
./scripts/setup.sh

# Windows PowerShell
.\scripts\setup.ps1
```

### 2. Start Infrastructure

```bash
docker compose up -d
```

This starts:
- PostgreSQL on port 5432 (`127.0.0.1:5432`)
- Redis on port 6379 (`127.0.0.1:6379`)
- Mailpit (email testing) on ports 1025/8025

For comprehensive setup instructions, see [Local Development Setup](docs/development/local-setup.md) and [Docker Environment Guide](docs/development/docker.md).

### 3. Run Applications

**Web (Next.js)**
```bash
cd apps/web
npm run dev
# http://localhost:3000
```

**API (Laravel)**
```bash
cd apps/api
php artisan serve
# http://localhost:8000
# Health: http://localhost:8000/api/v1/health
```

**Mobile (Flutter)**
```bash
cd apps/mobile
flutter run
```

**Desktop (.NET)**
```bash
cd apps/desktop
dotnet run --project src/FlowOS.Desktop
```

## Development

### Start All Services
```bash
# Linux/macOS
./scripts/dev.sh

# Windows PowerShell
.\scripts\dev.ps1
```

### Run Tests
```bash
# Linux/macOS
./scripts/test.sh

# Windows PowerShell
.\scripts\test.ps1
```

### Individual Test Commands

```bash
# Web
cd apps/web && npm run test

# API
cd apps/api && php artisan test

# Mobile
cd apps/mobile && flutter test

# Desktop
cd apps/desktop && dotnet test FlowOS.sln
```

## Key Architectural Decisions

| Decision | Document |
|----------|----------|
| Monorepo structure | [ADR 0001](docs/adr/0001-use-monorepo.md) |
| Next.js for web | [ADR 0002](docs/adr/0002-use-nextjs-for-web.md) |
| Laravel for API | [ADR 0003](docs/adr/0003-use-laravel-for-api.md) |
| PostgreSQL database | [ADR 0004](docs/adr/0004-use-postgresql.md) |
| Flutter for mobile | [ADR 0005](docs/adr/0005-use-flutter-for-mobile.md) |
| .NET for desktop | [ADR 0006](docs/adr/0006-use-dotnet-for-desktop.md) |
| Offline-first | [ADR 0007](docs/adr/0007-offline-first-architecture.md) |
| Multi-tenancy | [ADR 0008](docs/adr/0008-shared-schema-multitenancy.md) |
| UUIDv7 for IDs | [ADR 0009](docs/adr/0009-use-uuidv7.md) |
| Redis infrastructure | [ADR 0016](docs/adr/0016-redis-infrastructure.md) |
| Docker development environment | [ADR 0017](docs/adr/0017-docker-development-environment.md) |

## Documentation

- [System Overview](docs/architecture/system-overview.md)
- [Backend Architecture](docs/architecture/backend-architecture.md)
- [Frontend Architecture](docs/architecture/frontend-architecture.md)
- [Mobile Architecture](docs/architecture/mobile-architecture.md)
- [Desktop Architecture](docs/architecture/desktop-architecture.md)
- [Multi-Tenancy](docs/architecture/multi-tenancy.md)
- [ID Strategy](docs/architecture/id-strategy.md)
- [Money Handling](docs/architecture/money.md)
- [Deployment](docs/architecture/deployment-overview.md)
- [Docker Development Environment](docs/development/docker.md)
- [Local Setup Guide](docs/development/local-setup.md)
- [Infrastructure Services](docs/development/infrastructure-services.md)
- [Database Reset Workflows](docs/development/database-reset.md)
- [Troubleshooting Guide](docs/development/troubleshooting.md)
- [Development Setup](docs/engineering/development-setup.md)
- [Coding Standards](docs/engineering/coding-standards.md)
- [Git Workflow](docs/engineering/git-workflow.md)
- [Testing Strategy](docs/engineering/testing-strategy.md)
- [Offline-First Principles](docs/offline/offline-first-principles.md)

## Environment Variables

### Web (`apps/web/.env.local`)
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

### API (`apps/api/.env`)
```env
APP_NAME=flowOS
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=flowos
DB_USERNAME=flowos
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Contributing

Contributions from both human engineers and AI coding agents are welcome.

Please read:
- [Contribution Guidelines](CONTRIBUTING.md)
- [Git Workflow](docs/engineering/git-workflow.md)
- [Pull Request Workflow](docs/engineering/pull-request-workflow.md)
- [Coding Standards](docs/engineering/coding-standards.md)

## License

MIT License - see [LICENSE](LICENSE) for details.

## Support

- Documentation: `docs/`
- Issues: [GitHub Issues](https://github.com/your-org/flowos/issues)
- Discussions: [GitHub Discussions](https://github.com/your-org/flowos/discussions)