# Application Layer

Application-level use cases and orchestration. This layer contains:

- **Commands** - Write operations (mutations)
- **Queries** - Read operations
- **DTOs** - Data transfer objects
- **Handlers** - Command/Query handlers

## Structure

```
Application/
└── [Module]/
    ├── Commands/
    ├── Queries/
    ├── DTOs/
    └── Handlers/
```

## Example (future)

```
Application/
└── Inventory/
    ├── Commands/
    │   ├── CreateProductCommand.php
    │   ├── AdjustStockCommand.php
    │   └── ReserveStockCommand.php
    ├── Queries/
    │   ├── GetProductQuery.php
    │   └── ListProductsQuery.php
    ├── DTOs/
    │   ├── ProductDTO.php
    │   └── StockLevelDTO.php
    └── Handlers/
        ├── CreateProductHandler.php
        ├── AdjustStockHandler.php
        └── GetProductHandler.php
```

## Principles

1. Application layer coordinates domain objects
2. Handlers should be thin - delegate to domain services
3. Commands return void or result objects
4. Queries return DTOs, never entities
5. Use dependency injection for repositories