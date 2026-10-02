# ADR 0006: Use .NET for Desktop

## Title
Use .NET/C# for Desktop Application

## Status
ACCEPTED

## Context
Need a Windows desktop framework for POS/offline use with:
- Native Windows integration
- Offline-first capability
- Strong typing
- Good performance
- Team familiarity

## Decision
Use **.NET 8+ with WPF** and:
- CommunityToolkit.Mvvm for MVVM
- MediatR for CQRS
- Entity Framework Core + SQLite for local DB
- Microsoft.Extensions.* for DI/Config/Logging

## Consequences

### Positive
- **Windows Native**: Best Windows integration, touch/pen support
- **Performance**: Compiled, AOT ready, low memory
- **Type Safety**: C# 12 with nullable reference types
- **Ecosystem**: NuGet, mature libraries
- **Team Skills**: C# knowledge from desktop team
- **Offline-First**: EF Core + SQLite local database
- **Deployment**: Self-contained, single file, MSIX

### Negative
- **Windows Only**: No macOS/Linux (WPF)
- **XAML**: Learning curve, verbose
- **UI Framework**: WPF aging, WinUI 3 still maturing
- **Distribution**: Not in Store by default

### Mitigations
- Consider MAUI for future cross-platform
- Invest in WPF/WinUI expertise
- Use MSIX for Store distribution

## Alternatives Considered

### Electron (Web Tech)
- **Pros**: Single codebase with web, large ecosystem
- **Cons**: High memory, not native feel, security concerns

### WinUI 3 / MAUI
- **Pros**: Modern, cross-platform (MAUI)
- **Cons**: MAUI still maturing, bugs, smaller ecosystem

### Java (JavaFX/Swing)
- **Pros**: Cross-platform
- **Cons**: JVM overhead, dated UI, less Windows integration

### Qt (C++/Python)
- **Pros**: Cross-platform, mature
- **Cons**: Licensing (LGPL/commercial), C++ complexity

## Architecture

```
FlowOS.Desktop (WPF)
    ├── Views (XAML)
    ├── ViewModels (CommunityToolkit.Mvvm)
    ├── Services (Navigation, Dialogs)
    │
FlowOS.Application (Use Cases)
    ├── Commands/Queries (MediatR)
    ├── DTOs
    ├── Validators (FluentValidation)
    │
FlowOS.Domain (Core Logic)
    ├── Entities/ValueObjects
    ├── Services
    ├── Contracts
    │
FlowOS.Infrastructure (Impl)
    ├── EF Core + SQLite
    ├── Repositories
    ├── API Client
    │
FlowOS.Sync (Background)
    ├── SyncEngine (BackgroundService)
    ├── ConflictResolution
    ├── Queue
```

## Offline-First

```csharp
// Local SQLite with EF Core
public class FlowOSDbContext : DbContext {
    public DbSet<Product> Products => Set<Product>();
    public DbSet<SyncOperation> SyncQueue => Set<SyncOperation>();

    protected override void OnConfiguring(DbContextOptionsBuilder options) {
        options.UseSqlite("Data Source=flowos.db");
    }
}

// Background sync
public class SyncBackgroundService : BackgroundService {
    protected override async Task ExecuteAsync(CancellationToken stoppingToken) {
        while (!stoppingToken.IsCancellationRequested) {
            await _syncEngine.SyncAsync();
            await Task.Delay(TimeSpan.FromMinutes(5), stoppingToken);
        }
    }
}
```

## References
- [.NET 8](https://dotnet.microsoft.com/en-us/download/dotnet/8.0)
- [WPF](https://docs.microsoft.com/en-us/dotnet/desktop/wpf/)
- [CommunityToolkit.Mvvm](https://github.com/CommunityToolkit/dotnet)
