# Domain Layer

Core business rules and domain logic. This layer contains:

- **Entities** - Core domain objects with identity
- **Value Objects** - Immutable objects representing descriptive aspects
- **Services** - Domain services encapsulating business logic
- **Events** - Domain events for communication
- **Contracts** - Interfaces for repositories and external services

## Structure

```
Domain/
└── [Module]/
    ├── Entities/
    ├── ValueObjects/
    ├── Services/
    ├── Events/
    └── Contracts/
```

## Example (future)

```
Domain/
└── Inventory/
    ├── Entities/
    │   ├── Product.php
    │   └── StockLevel.php
    ├── ValueObjects/
    │   ├── SKU.php
    │   └── Quantity.php
    ├── Services/
    │   └── StockReservationService.php
    ├── Events/
    │   └── StockAdjusted.php
    └── Contracts/
        └── ProductRepository.php
```

## Principles

1. Domain layer has no dependencies on outer layers
2. Business rules live here, not in controllers or services
3. Use domain events for cross-aggregate communication
4. Entities should protect their invariants