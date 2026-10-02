# flowOS Desktop Application

The .NET/WPF desktop application for flowOS — an offline-capable business operating system for Nigerian businesses.

## Technology Stack

- **Framework**: .NET 8.0 (LTS)
- **UI Framework**: WPF (Windows Presentation Foundation)
- **Architecture**: Clean Architecture (Domain, Application, Infrastructure, Sync)
- **DI/Configuration**: Microsoft.Extensions.Hosting, DependencyInjection, Configuration
- **Testing**: xUnit, FluentAssertions, Moq
- **MVVM**: CommunityToolkit.Mvvm (for future use)

## Solution Structure

```
apps/desktop/
├── src/
│   ├── FlowOS.Desktop/          # WPF Application (UI)
│   ├── FlowOS.Domain/           # Core business logic (pure)
│   ├── FlowOS.Application/      # Use cases, commands, queries
│   ├── FlowOS.Infrastructure/   # Technical implementations (EF Core, HTTP, etc.)
│   └── FlowOS.Sync/             # Synchronization engine
│
├── tests/
│   ├── FlowOS.Domain.Tests/
│   ├── FlowOS.Application.Tests/
│   └── FlowOS.Infrastructure.Tests/
│
├── FlowOS.sln
├── .env.example
└── README.md
```

## Architecture

### Dependency Direction

```
FlowOS.Domain          (Pure business logic, no external deps)
      ↑
FlowOS.Application     (Use cases, commands, queries)
      ↑
FlowOS.Infrastructure  (EF Core, HTTP clients, repositories)
      ↑
FlowOS.Desktop         (WPF UI, composition root)
```

`FlowOS.Sync` references Domain, Application, and Infrastructure for synchronization logic.

### Key Principles

- **Domain layer** has zero external dependencies (only System.ComponentModel.Annotations)
- **Application layer** depends only on Domain
- **Infrastructure** implements Domain/Application contracts
- **Desktop** composes everything at startup
- **Sync** orchestrates between local DB and cloud API

## Development Setup

### Prerequisites

- .NET 8.0 SDK (LTS)
- Windows 10/11 for WPF development
- Visual Studio 2022 or VS Code with C# Dev Kit

### Commands

```bash
# Restore dependencies
dotnet restore

# Build solution
dotnet build

# Run tests
dotnet test

# Format code
dotnet format

# Verify formatting
dotnet format --verify-no-changes

# Run desktop app
dotnet run --project src/FlowOS.Desktop
```

## Configuration

Create `.env` from `.env.example`:

```bash
# API
API_BASE_URL=http://localhost:8000/api/v1

# Application
APP_ENV=Development
APP_URL=http://localhost:8000

# Database (future local SQLite)
DATABASE_PATH=flowos.db

# Logging
LOG_LEVEL=Debug
```

## Architecture Responsibilities

| Project | Responsibility |
|---------|---------------|
| `FlowOS.Domain` | Entities, value objects, domain services, events, contracts |
| `FlowOS.Application` | Commands, queries, DTOs, validators, handlers |
| `FlowOS.Infrastructure` | EF Core, SQLite, HTTP clients, repositories, config |
| `FlowOS.Sync` | Sync engine, outbox, conflict resolution, cursors |
| `FlowOS.Desktop` | WPF views, view models, composition root |

## Testing

| Project | Framework | Focus |
|---------|-----------|-------|
| `FlowOS.Domain.Tests` | xUnit | Domain logic, value objects, entities |
| `FlowOS.Application.Tests` | xUnit + Moq | Handlers, validators, use cases |
| `FlowOS.Infrastructure.Tests` | xUnit + EF Core InMemory | Repositories, EF config |

```bash
# Run all tests
dotnet test

# Run specific project
dotnet test tests/FlowOS.Domain.Tests
```

## Offline-First Architecture

Future offline-capable operations:

```
Desktop UI
    ↓
Application Layer
    ↓
Local Database (SQLite)
    ↓
Sync Engine
    ↓
Laravel API
    ↓
PostgreSQL
```

- Local writes first (optimistic)
- Queue for background sync
- Domain-specific conflict resolution; no universal server-wins or last-writer-wins rule
- Sync cursor management

## Multi-Tenancy

- `Organization` is the primary tenant
- Future: `Branch`, `User`, `Device` scoped to organization
- `organization_id` on all tenant-owned records
- Tenant resolution via subdomain or header

## Money Handling

- Default currency: **NGN**
- Use `decimal` for calculations (never `double`/`float`)
- Store as integer minor units (kobo) or field-specific `NUMERIC(p,s)` in DB
- Formatting via `CultureInfo`

## Date & Time

- Store UTC in database
- Convert to organization timezone for display
- Default organization timezone: `Africa/Lagos`

## Security

- No secrets in source code
- Use `.env` for local development
- Production: Azure Key Vault / environment variables
- Never log tokens/passwords

## Future Work (Not Implemented)

- [ ] Authentication (Sanctum integration)
- [ ] Organization/Branch onboarding
- [ ] POS module
- [ ] Inventory management
- [ ] Products/Categories
- [ ] Customers/Suppliers
- [ ] Sales/Invoicing
- [ ] Accounting/Chart of accounts
- [ ] Local SQLite database
- [ ] Background sync service
- [ ] Conflict resolution UI
- [ ] Background sync (WorkManager equivalent)
- [ ] Biometric auth (Windows Hello)

## License

Proprietary — Copyright (c) flowOS. All rights reserved.
