# Infrastructure Layer

Technical implementations and external integrations. This layer contains:

- **Repositories** - Database implementations of domain contracts
- **External Services** - Payment gateways, SMS, email providers
- **Persistence** - Eloquent models, migrations, seeders
- **Queue Infrastructure** - Jobs, listeners, workers
- **Sync Infrastructure** - Offline synchronization logic

## Structure

```
Infrastructure/
├── Persistence/
│   ├── Eloquent/
│   ├── Repositories/
│   └── Migrations/
├── External/
│   ├── Payments/
│   ├── Notifications/
│   └── Integrations/
├── Queue/
│   ├── Jobs/
│   └── Listeners/
└── Sync/
    ├── Engine/
    └── Strategies/
```

## Principles

1. Implements contracts defined in Domain layer
2. Can depend on frameworks (Laravel, Eloquent)
3. Repository implementations hide query complexity
4. External services wrapped in adapters
5. Queue jobs should be idempotent where possible