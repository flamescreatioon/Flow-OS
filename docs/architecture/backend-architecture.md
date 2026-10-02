# Backend Architecture

## Laravel Application Structure

```
apps/api/
├── app/
│   ├── Domain/           # Core business logic
│   │   └── [Module]/
│   │       ├── Entities/
│   │       ├── ValueObjects/
│   │       ├── Services/
│   │       ├── Events/
│   │       └── Contracts/
│   ├── Application/      # Use cases
│   │   └── [Module]/
│   │       ├── Commands/
│   │       ├── Queries/
│   │       ├── DTOs/
│   │       └── Handlers/
│   ├── Infrastructure/   # Technical implementations
│   │   ├── Persistence/
│   │   ├── External/
│   │   ├── Queue/
│   │   └── Sync/
│   ├── Http/             # API layer
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/           # Eloquent models
│   ├── Policies/         # Authorization
│   ├── Providers/        # Service providers
│   └── Support/          # Helpers, traits
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── routes/
│   ├── api.php
│   ├── web.php
│   └── console.php
└── tests/
    ├── Unit/
    └── Feature/
```

## Layer Responsibilities

### Domain Layer
- Pure business logic, no framework dependencies
- Entities with invariants protected
- Value objects for immutable concepts
- Domain services for cross-entity logic
- Domain events for side effects
- Repository contracts (interfaces)

### Application Layer
- Orchestrates domain objects
- Command/Query handlers
- DTOs for data transfer
- Transaction boundaries
- Calls domain services and repositories

### Infrastructure Layer
- Implements domain contracts
- Eloquent models and repositories
- External service adapters
- Queue job implementations
- Database migrations
- Sync engine

### HTTP Layer
- Controllers (thin, delegate to Application)
- Form Requests (validation)
- API Resources (serialization)
- Middleware (auth, tenant, rate limit)

## Multi-Tenancy

Using `spatie/laravel-multitenancy` with shared schema:

```php
// Tenant identification
- Subdomain: org.flowos.local
- Header: X-Organization-ID
- JWT claim: organization_id

// Automatic scoping
- Global scopes on tenant models
- Queue tenant awareness
- Cache prefixing per tenant
```

## ID Strategy

All primary keys use **UUIDv7**:
- Sortable by creation time
- Globally unique for offline sync
- No coordination needed

```php
// Trait for models
trait UsesUuidv7 {
    protected static function booted() {
        static::creating(fn ($model) => $model->id = Uuid::uuid7()->toString());
    }
}
```

## Money Handling

Never use floats for money:

```php
// Store as integer minor units (kobo for NGN)
$amount = 150000; // ₦1,500.00

// Or use PostgreSQL NUMERIC
$table->decimal('amount', 19, 4);
```

## API Design

- RESTful endpoints under `/api/v1/`
- JSON request/response
- Standard HTTP status codes
- Pagination with `page`/`per_page`
- Filtering via query parameters
- Consistent error format

```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

## Testing

- Unit tests for domain logic
- Feature tests for API endpoints
- Pest PHP for expressive tests
- SQLite in-memory for CI
- Factory/seed for test data