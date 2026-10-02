# ADR 0003: Use Laravel for API

## Title
Use Laravel for API Backend

## Status
Accepted

## Context
Need a PHP backend framework for the API with:
- Rapid development
- Built-in authentication (Sanctum)
- Multi-tenancy support
- Queue workers
- Database migrations
- Testing tools
- Strong ecosystem in Nigeria/Africa

## Decision
Use **Laravel 11+** with:
- Laravel Sanctum for API auth
- spatie/laravel-multitenancy for multi-tenancy
- PHP 8.2+
- PostgreSQL database

## Consequences

### Positive
- **Productivity**: Eloquent ORM, artisan commands, conventions
- **Multi-tenancy**: spatie package handles tenant isolation
- **Auth**: Sanctum provides token-based auth out of the box
- **Queues**: Built-in queue workers with Redis
- **Testing**: Pest/PHPUnit with excellent test helpers
- **Ecosystem**: Packages for everything (Spatie, Laravel Cashier, etc.)
- **African Market**: Popular in Nigeria, good talent pool

### Negative
- **PHP Runtime**: Not as fast as Node/Go
- **Monolith Tendency**: Easy to create spaghetti code
- **Memory**: Higher per-request memory than compiled langs
- **Concurrency**: Process-based, not event-loop

### Mitigations
- Use Domain/Application/Infrastructure layering
- OPcache + JIT in PHP 8.2+
- Horizontal scaling with load balancer
- Redis for caching/sessions

## Alternatives Considered

### Node.js (Express/NestJS)
- **Pros**: Same language as frontend, high concurrency
- **Cons**: No batteries-included, more boilerplate, auth/multi-tenancy manual

### Go (Gin/Fiber)
- **Pros**: Performance, concurrency, single binary
- **Cons**: More verbose, less ecosystem for business apps, team expertise

### Python (FastAPI/Django)
- **Pros**: FastAPI performance, Django admin
- **Cons**: Django heavy, FastAPI less batteries, async complexity

### .NET (ASP.NET Core)
- **Pros**: Performance, strong typing, great tooling
- **Cons**: Team expertise in PHP, desktop already .NET

## References
- [Laravel 11](https://laravel.com/docs/11.x)
- [Sanctum](https://laravel.com/docs/sanctum)
- [spatie/laravel-multitenancy](https://spatie.be/docs/laravel-multitenancy)