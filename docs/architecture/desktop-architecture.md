# Desktop Architecture

## .NET Application Structure

```
apps/desktop/
├── src/
│   ├── FlowOS.Desktop/        # WPF Application (Presentation)
│   │   ├── Views/             # XAML Views
│   │   ├── ViewModels/        # MVVM ViewModels
│   │   ├── Services/          # UI-specific services
│   │   ├── Converters/        # Value converters
│   │   ├── Behaviors/         # Attached behaviors
│   │   ├── App.xaml           # Application resources
│   │   ├── MainWindow.xaml    # Main window
│   │   └── Program.cs         # Entry point
│   ├── FlowOS.Application/    # Application Layer (Use Cases)
│   │   ├── Commands/          # Write operations
│   │   ├── Queries/           # Read operations
│   │   ├── DTOs/              # Data transfer objects
│   │   ├── Handlers/          # MediatR handlers
│   │   └── Validators/        # FluentValidation
│   ├── FlowOS.Domain/         # Domain Layer (Core Logic)
│   │   ├── Entities/          # Domain entities
│   │   ├── ValueObjects/      # Immutable values
│   │   ├── Services/          # Domain services
│   │   ├── Events/            # Domain events
│   │   ├── Contracts/         # Repository interfaces
│   │   └── Exceptions/        # Domain exceptions
│   ├── FlowOS.Infrastructure/ # Infrastructure Layer
│   │   ├── Persistence/       # EF Core, SQLite
│   │   │   ├── Configurations/
│   │   │   ├── Migrations/
│   │   │   ├── Repositories/
│   │   │   └── FlowOSDbContext.cs
│   │   ├── External/          # API clients, integrations
│   │   ├── Services/          # Infrastructure services
│   │   └── DependencyInjection.cs
│   └── FlowOS.Sync/           # Sync Engine
│       ├── Engine/            # Sync orchestration
│       ├── Strategies/        # Conflict resolution
│       ├── Queue/             # Local operation queue
│       └── BackgroundService.cs
├── tests/
│   ├── FlowOS.Domain.Tests/
│   ├── FlowOS.Application.Tests/
│   └── FlowOS.Infrastructure.Tests/
└── FlowOS.sln
```

## Architecture Pattern: Clean Architecture + MVVM

```
┌─────────────────────────────────────────────────────────────┐
│                     FlowOS.Desktop (WPF)                     │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────┐   │
│  │   Views     │ │ ViewModels  │ │  Services (UI)      │   │
│  │  (XAML)     │ │ (Community  │ │  - Navigation       │   │
│  │             │ │  Toolkit)   │ │  - Dialogs          │   │
│  └──────┬──────┘ └──────┬──────┘ └──────────┬──────────┘   │
└─────────┼───────────────┼───────────────────┼───────────────┘
          │               │                   │
          ▼               ▼                   ▼
┌─────────────────────────────────────────────────────────────┐
│                   FlowOS.Application                         │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────┐   │
│  │  Commands   │ │  Queries    │ │  Validators         │   │
│  │  Handlers   │ │  Handlers   │ │  (FluentValidation) │   │
│  └──────┬──────┘ └──────┬──────┘ └──────────┬──────────┘   │
└─────────┼───────────────┼───────────────────┼───────────────┘
          │               │                   │
          ▼               ▼                   ▼
┌─────────────────────────────────────────────────────────────┐
│                     FlowOS.Domain                            │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────┐   │
│  │  Entities   │ │Value Objects│ │  Domain Services    │   │
│  │             │ │             │ │  - Business Rules   │   │
│  └──────┬──────┘ └──────┬──────┘ └──────────┬──────────┘   │
└─────────┼───────────────┼───────────────────┼───────────────┘
          │               │                   │
          ▼               ▼                   ▼
┌─────────────────────────────────────────────────────────────┐
│                 FlowOS.Infrastructure                        │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────┐   │
│  │ EF Core     │ │ Repositories│ │  External Services  │   │
│  │ (SQLite)    │ │ (Impl.)     │ │  - API Client       │   │
│  └─────────────┘ └─────────────┘ └─────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Key Technologies

| Layer | Technology |
|-------|------------|
| UI Framework | WPF (.NET 8) |
| MVVM | CommunityToolkit.Mvvm |
| DI | Microsoft.Extensions.DependencyInjection |
| Mediation | MediatR |
| Validation | FluentValidation |
| Local DB | Entity Framework Core + SQLite |
| API Client | HttpClient / Refit |
| Logging | Microsoft.Extensions.Logging |
| Sync | Custom BackgroundService |

## Offline-First Implementation

### Local Database (SQLite)
```csharp
// EF Core with SQLite for local persistence
public class FlowOSDbContext : DbContext {
    public DbSet<Organization> Organizations { get; set; }
    public DbSet<Branch> Branches { get; set; }
    public DbSet<User> Users { get; set; }
    public DbSet<SyncOperation> SyncQueue { get; set; }

    protected override void OnConfiguring(DbContextOptionsBuilder options) {
        options.UseSqlite("Data Source=flowos.db");
    }
}
```

### Sync Engine
```csharp
// Background service for sync
public class SyncBackgroundService : BackgroundService {
    private readonly ISyncEngine _syncEngine;

    protected override async Task ExecuteAsync(CancellationToken stoppingToken) {
        while (!stoppingToken.IsCancellationRequested) {
            await _syncEngine.SyncAsync();
            await Task.Delay(TimeSpan.FromMinutes(5), stoppingToken);
        }
    }
}
```

### Conflict Resolution
- **Last-Write-Wins** with server authority
- **Timestamp-based** using UTC
- **Optimistic concurrency** with row versions

## Dependency Flow

```
FlowOS.Desktop
    → FlowOS.Application
    → FlowOS.Domain
    → FlowOS.Infrastructure
    → FlowOS.Sync
```

- Desktop depends on Application
- Application depends on Domain
- Infrastructure implements Domain contracts
- Sync depends on Application + Infrastructure

## Testing Strategy

| Project | Focus | Status |
|---------|-------|--------|
| Domain.Tests | Pure business logic, no infrastructure | INTENTIONALLY EMPTY (tests to be added) |
| Application.Tests | Handlers, validators, use cases | INTENTIONALLY EMPTY (tests to be added) |
| Infrastructure.Tests | Repository implementations, EF Core | INTENTIONALLY EMPTY (tests to be added) |

*All three test projects are correctly wired with Microsoft.NET.Test.Sdk, xUnit, and xunit.runner.visualstudio. Zero-test discovery is intentional (no test classes yet) and not a configuration failure.*

## Build & Run

```bash
# Restore
dotnet restore FlowOS.sln

# Build
dotnet build FlowOS.sln --configuration Release

# Run tests
dotnet test FlowOS.sln

# Run desktop
dotnet run --project src/FlowOS.Desktop/FlowOS.Desktop.csproj
```

## Packaging

- Self-contained deployment
- Single-file executable option
- MSIX packaging for Windows Store
- Auto-update via GitHub Releases
