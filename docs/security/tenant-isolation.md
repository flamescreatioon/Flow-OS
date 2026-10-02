# Tenant Isolation Security

## Core Principles

### 1. Tenant Identification ≠ Tenant Authorization

**CRITICAL**: Resolving an organization from a request does NOT grant access.

```php
// DANGEROUS - Never do this
public function handle(Request $request, Closure $next) {
    $orgId = $request->header('X-Organization-ID');
    $org = Organization::find($orgId);
    Multitenancy::makeCurrent($org); // DANGEROUS: No authorization check!
    return $next($request);
}

// SAFE - Authorization check required
public function handle(Request $request, Closure $next) {
    $orgId = $request->header('X-Organization-ID');
    $org = Organization::find($orgId);

    if ($org && $request->user()->canAccessOrganization($org->id)) {
        Multitenancy::makeCurrent($org);
    }

    return $next($request);
}
```

### 2. Every Tenant-Owned Query Must Be Scoped

Sprint 0.10 implements tenant authorization with an active `organization_memberships` record. `users.organization_id` is legacy compatibility data and cannot authorize tenant context by itself.

```sql
-- Good - tenant scoped
SELECT * FROM products WHERE organization_id = $1 AND id = $2;

-- Bad - missing tenant scope (data leak risk)
SELECT * FROM products WHERE id = $1;
```

### 3. IDs Are Not Authorization

```php
// WRONG - ID alone is not authorization
if ($request->has('organization_id')) {
    // Process without verifying access
}

// CORRECT
if ($request->user()->canAccessOrganization($orgId)) {
    // Proceed
}
```

### 4. UUID Secrecy Is Not Security

- UUIDs are not secret
- Do not rely on UUID obscurity for security
- Always enforce authorization checks

## Mandatory Rules

### 1. Every Tenant-Owned Query Must Be Scoped

```sql
-- Good
SELECT * FROM products WHERE organization_id = $1 AND id = $2;

-- Bad - missing tenant scope (data leak risk)
SELECT * FROM products WHERE id = $1;
```

### 2. Global Scopes on All Tenant Models

```php
trait BelongsToOrganization {
    protected static function booted(): void {
        static::addGlobalScope('organization', function (Builder $builder) {
            $orgId = app(Multitenancy::class)->getTenant()?->id;
            if ($orgId) {
                $builder->where('organization_id', $orgId);
            }
        });
    }
}
```

### 3. IDs Are Not Authorization

```php
// WRONG - ID alone is not authorization
if ($request->has('organization_id')) {
    // Process without verifying access
}

// CORRECT
if ($request->user()->canAccessOrganization($orgId)) {
    // Proceed
}
```

### 4. UUID Secrecy Is Not Security

- UUIDs are not secret
- Do not rely on UUID obscurity for security
- Always enforce authorization checks

### 5. Tenant Context Must Propagate Into Async Work

```php
class ProcessSale implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $saleId,
        public string $organizationId  // Explicit context
    ) {}

    public function handle() {
        Multitenancy::setTenant($this->organizationId);
        try {
            // Process with tenant context
        } finally {
            Multitenancy::forgetTenant();
        }
    }
}
```

### 6. Cross-Tenant Operations Require Explicit Paths

```php
// Only in privileged contexts
class CrossTenantReport {
    public function generate(Organization $org1, Organization $org2) {
        // Explicit cross-tenant operation
        // Requires platform_admin permission
        $this->authorize('platform_admin');

        $data1 = Product::onTenant($org1)->get();
        $data2 = Product::onTenant($org2)->get();
    }
}
```

### 7. Logs Should Include Organization Context

```php
Log::info('Sale processed', [
    'organization_id' => $orgId,
    'sale_id' => $saleId,
    'user_id' => auth()->id(),
]);
```

### 8. Tests Must Verify Isolation

```php
public function test_tenant_isolation() {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    Product::factory()->for($org1)->create(['name' => 'Product A']);
    Product::factory()->for($org2)->create(['name' => 'Product B']);

    $this->actingAsTenant($org1);
    $products = Product::all();

    $this->assertCount(1, $products);
    $this->assertEquals('Product A', $products->first()->name);
}
```

## Anti-Patterns to Avoid

| Anti-Pattern | Risk | Correct Approach |
|--------------|------|------------------|
| Trust `X-Organization-ID` header | Data leak | Verify user membership |
| Global scope missing | Data leak | Apply global scope on all tenant models |
| Cache without tenant prefix | Cross-tenant data leak | Prefix cache keys: `org:{id}:key` |
| Job without org context | Cross-tenant data leak | Pass `organization_id` in job payload |
| Admin bypasses all scopes | Data leak | Explicit platform admin check |
| Error messages leak org existence | Information disclosure | Generic error messages |
| Soft delete without tenant scope | Data leak | Scope soft deletes by org |
| UUID as secret | ID enumeration | Use authorization checks |
