# System Overview

## flowOS Architecture

flowOS is an offline-capable business operating system designed for Nigerian businesses. The architecture follows a modular monolith pattern with clear separation of concerns.

```
┌─────────────────────────────────────────────────────────────┐
│                        Clients                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │   Web    │  │ Mobile   │  │ Desktop  │  │  Future  │    │
│  │ (Next.js)│  │ (Flutter)│  │ (.NET)   │  │  Clients │    │
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

## Core Principles

1. **Modular Monolith** - Single deployable unit with clear internal boundaries
2. **Offline-First** - All clients can operate without internet connectivity
3. **Multi-Tenant** - Shared database with organization-level isolation
4. **API-First** - All functionality exposed via REST API
5. **Type Safety** - End-to-end type safety via OpenAPI contracts

## Technology Stack

| Layer | Technology |
|-------|------------|
| Web Frontend | Next.js 14, React 18, TypeScript, Tailwind CSS |
| API Backend | Laravel 11, PHP 8.2 |
| Mobile | Flutter 3, Dart 3, Riverpod |
| Desktop | .NET 8, WPF, C# |
| Database | PostgreSQL 16 |
| Cache/Queue | Redis 7 |
| Infrastructure | Docker, Docker Compose |

## Data Flow

### Online Mode
```
User Action → Client → API → Database → Response → Client → UI Update
```

### Offline Mode
```
User Action → Client → Local DB → UI Update → Sync Queue → (later) API → Database
```

## Security

- Token-based authentication (Laravel Sanctum)
- Organization-scoped data access
- Rate limiting on API endpoints
- HTTPS in production
- Input validation at API boundary

## Scalability

- Horizontal scaling via load balancer
- Redis for caching and queue workers
- Database read replicas (future)
- Stateless API servers