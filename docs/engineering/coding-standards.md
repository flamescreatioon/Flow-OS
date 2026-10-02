# Coding Standards (Legacy Reference)

The authoritative cross-language merge standard is [Code Quality Standards](code-quality.md). This document remains for examples and historical guidance; where it conflicts with the quality standard or a focused architecture document, the focused document wins.

## General Principles

1. **Consistency over preference** - Follow existing patterns
2. **Explicit over implicit** - Clear code beats clever code
3. **Types over comments** - Type system documents intent
4. **Cohesive functions** - Refactor for responsibility, testability, or excessive mental state; there is no universal line-count limit
5. **Early returns** - Reduce nesting

## TypeScript / JavaScript

### Type Safety
```typescript
// ✅ Good - explicit types
function calculateTotal(items: LineItem[]): Money {
  return items.reduce((sum, item) => add(sum, item.total), zeroMoney());
}

// ❌ Bad - any types
function calculateTotal(items: any[]): any {
  return items.reduce((sum, item) => sum + item.total, 0);
}
```

### Naming
```typescript
// ✅ Good
const MAX_RETRY_ATTEMPTS = 3;
const userRepository = new UserRepository();
function getUserById(id: UUID): Promise<User | null> { }

// ❌ Bad
const max = 3;
const repo = new UserRepository();
function get(id) { }
```

### Imports
```typescript
// ✅ Good - grouped, sorted
import { type User, type Organization } from '@/types';
import { api } from '@/lib/api';
import { useAuth } from '@/hooks/use-auth';
import Button from '@/components/ui/Button';

// ❌ Bad - random order
import { api } from '@/lib/api';
import { type User } from '@/types';
import Button from '@/components/ui/Button';
```

### React Components
```typescript
// ✅ Good - typed props, default exports for pages
interface UserCardProps {
  user: User;
  onEdit?: (id: UUID) => void;
}

export function UserCard({ user, onEdit }: UserCardProps) {
  return (
    <div className="p-4 border rounded-lg">
      <h3 className="font-semibold">{user.name}</h3>
      {onEdit && <Button onClick={() => onEdit(user.id)}>Edit</Button>}
    </div>
  );
}
```

## PHP (Laravel)

### Strict Types
```php
<?php

declare(strict_types=1);

namespace App\Application\Inventory\Commands;

use App\Domain\Inventory\ValueObjects\SKU;
use App\Domain\Inventory\Contracts\ProductRepository;

final readonly class CreateProductCommand {
    public function __construct(
        public string $name,
        public SKU $sku,
        public int $priceInMinorUnits,
        public string $currency = 'NGN',
    ) {}
}
```

### Controllers
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Application\Inventory\Commands\CreateProductCommand;
use Illuminate\Http\JsonResponse;

final class ProductController extends Controller {
    public function store(StoreProductRequest $request): JsonResponse {
        $command = new CreateProductCommand(
            name: $request->validated('name'),
            sku: new SKU($request->validated('sku')),
            priceInMinorUnits: $request->validated('price_in_minor_units'),
            currency: $request->validated('currency') ?? 'NGN',
        );

        $product = $this->dispatch($command);

        return new ProductResource($product)
            ->response()
            ->setStatusCode(201);
    }
}
```

### Models
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class Product extends Model {
    protected $table = 'products';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'organization_id', 'name', 'sku',
        'price_in_minor_units', 'currency'
    ];

    protected $casts = [
        'price_in_minor_units' => 'integer',
    ];

    protected static function booted(): void {
        static::creating(fn ($m) => $m->id = Uuid::uuid7()->toString());
        static::addGlobalScope('organization', fn ($q) =>
            $q->where('organization_id', tenant_id())
        );
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }
}
```

## Dart (Flutter)

### Null Safety
```dart
// ✅ Good - non-nullable by default
class Product {
  final String id;
  final String name;
  final int priceInMinorUnits;
  final String currency;

  const Product({
    required this.id,
    required this.name,
    required this.priceInMinorUnits,
    this.currency = 'NGN',
  });
}

// ❌ Bad - nullable without reason
class Product {
  String? id;
  String? name;
  int? price;
}
```

### Freezed Models
```dart
@freezed
class Product with _$Product {
  const factory Product({
    required String id,
    required String name,
    required int priceInMinorUnits,
    @Default('NGN') String currency,
  }) = _Product;

  factory Product.fromJson(Map<String, dynamic> json) => _$ProductFromJson(json);
}
```

### Riverpod Providers
```dart
// ✅ Good - explicit, testable
final productRepositoryProvider = Provider<ProductRepository>((ref) {
  return ProductRepositoryImpl(ref.watch(apiClientProvider));
});

final productsProvider = FutureProvider<List<Product>>((ref) async {
  return ref.read(productRepositoryProvider).getAll();
});
```

## C# (.NET)

### Nullable Reference Types
```csharp
// ✅ Good - enabled in project
#nullable enable

public record Product(
    string Id,
    string Name,
    long PriceInMinorUnits,
    string Currency = "NGN"
);

// Repository interface
public interface IProductRepository {
    Task<Product?> GetByIdAsync(string id, CancellationToken ct = default);
    Task<IReadOnlyList<Product>> GetAllAsync(CancellationToken ct = default);
    Task AddAsync(Product product, CancellationToken ct = default);
}
```

### MediatR Handlers
```csharp
public sealed record CreateProductCommand(
    string Name,
    string Sku,
    long PriceInMinorUnits,
    string Currency = "NGN"
) : IRequest<Product>;

public sealed class CreateProductHandler : IRequestHandler<CreateProductCommand, Product> {
    private readonly IProductRepository _repository;

    public CreateProductHandler(IProductRepository repository) {
        _repository = repository;
    }

    public async Task<Product> Handle(CreateProductCommand request, CancellationToken ct) {
        var product = new Product(
            Id: UuidV7.NewGuid().ToString(),
            Name: request.Name,
            PriceInMinorUnits: request.PriceInMinorUnits,
            Currency: request.Currency
        );

        await _repository.AddAsync(product, ct);
        return product;
    }
}
```

## Database

### Migrations
```php
// ✅ Good - reversible, descriptive
Schema::create('products', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
    $table->string('name', 255);
    $table->string('sku', 100);
    $table->unsignedBigInteger('price_in_minor_units');
    $table->char('currency', 3)->default('NGN');
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['organization_id', 'sku']);
    $table->index('organization_id');
});
```

### Naming Conventions
- Tables: `snake_case`, plural (`products`, `sale_items`)
- Columns: `snake_case` (`price_in_minor_units`, `created_at`)
- Indexes: `idx_{table}_{columns}` (`idx_products_org_sku`)
- Foreign keys: `fk_{table}_{ref_table}` (`fk_products_organizations`)

## Git Commits

### Conventional Commits
```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Types
| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation |
| `style` | Formatting |
| `refactor` | Code restructuring |
| `test` | Adding tests |
| `chore` | Maintenance |

### Examples
```
feat(auth): add OAuth2 login with Google

fix(api): resolve UUID validation on Windows

docs(architecture): add multi-tenancy diagram

refactor(domain): extract money value object

test(application): add create product handler tests
```

## Code Review Checklist

- [ ] Types are explicit and correct
- [ ] No `any` / `dynamic` / `object` without justification
- [ ] Business logic in domain/application, not controllers/UI
- [ ] Tests cover happy path and edge cases
- [ ] No secrets or credentials in code
- [ ] Migrations are reversible
- [ ] Breaking changes documented
- [ ] Performance considered (N+1 queries, etc.)
