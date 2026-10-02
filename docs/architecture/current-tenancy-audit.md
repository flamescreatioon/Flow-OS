# Current Tenancy Implementation Audit

## Organization Model

**File**: `apps/api/app/Models/Organization.php`

```php
class Organization extends Model implements MultitenancyTenant
{
    use HasFactory, UsesTenantDatabase, UsesTenantId;

    protected $table = 'organizations';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'slug', 'timezone', 'currency', 'settings',
    ];

    protected $casts = ['settings' => 'array'];

    protected static function booted(): void {
        static::creating(fn ($model) =>
            empty($model->id) && $model->id = Uuid::uuid7()->toString()
        );
    }

    public function branches() { return $this->hasMany(Branch::class); }
    public function users() { return $this->hasMany(User::class); }

    public function getTenantDatabaseName(): string { return 'flowos_'.$this->id; }
    public function getTenantDatabasePrefix(): string { return ''; }
}
```

**Status**: ✅ Implements `MultitenancyTenant`, uses `UsesTenantDatabase` and `UsesTenantId` traits.

**Issues**:
- Uses `UsesTenantDatabase` trait (database-per-tenant) but we use shared schema
- `getTenantDatabaseName()` suggests database-per-tenant but we use shared schema

---

## Branch Model

**File**: `apps/api/app/Models/Branch.php`

```php
class Branch extends Model {
    use HasFactory;
    protected $table = 'branches';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'organization_id', 'name', 'address', 'phone', 'email', 'is_main',
    ];

    protected $casts = ['is_main' => 'boolean'];

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }
}
```

**Status**: ✅ Has `organization_id` FK with CASCADE delete

**Issues**:
- No global scope for `organization_id`
- No `BelongsToOrganization` trait

---

## User Model

**File**: `apps/api/app/Models/User.php`

```php
class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'id', 'organization_id', 'name', 'email', 'password', 'role', 'is_active',
    ];

    protected static function booted(): void {
        static::creating(fn ($model) =>
            empty($model->id) && $model->id = Uuid::uuid7()->toString()
        );
    }

    public function organization() {
        return $this->belongsTo(Organization::class);
    }
}
```

**Issues**:
- ❌ No `BelongsToOrganization` trait / global scope
- ❌ No global scope for `organization_id`
- `organization_id` is fillable but not protected by global scope
- Single `organization_id` - cannot belong to multiple organizations

---

## Tenant Middleware

**File**: `apps/api/app/Http/Middleware/TenantMiddleware.php`

```php
class TenantMiddleware {
    public function handle(Request $request, Closure $next): Response {
        $tenant = $this->findTenant($request);
        if ($tenant) {
            app(Multitenancy::class)->makeCurrent($tenant);
        }
        return $next($request);
    }

    protected function findTenant(Request $request): ?Tenant {
        $domain = $request->getHost();
        $subdomain = $this->extractSubdomain($domain);
        if ($subdomain) {
            return Organization::where('slug', $subdomain)->first();
        }
        $organizationId = $request->header('X-Organization-ID');
        if ($organizationId) {
            return Organization::find($organizationId);
        }
        return null;
    }
}
```

**Issues**:
- ❌ No user membership verification
- ❌ No JWT claim resolution
- ❌ No session fallback
- ❌ No authorization check - trusts any provided organization ID
- ❌ No tenant context clearing on response

---

## Spatie Multitenancy Configuration

**File**: `config/multitenancy.php`

```php
'tenant_model' => Organization::class,
'tenant_finder' => DomainTenantFinder::class,
'switch_tenant_tasks' => [
    SwitchTenantDatabaseTask::class,
    PrefixCacheTask::class,
    SwitchRouteCacheTask::class,
],
'queues' => ['tenant_key' => 'organization_id'],
```

**Observations**:
- Uses `DomainTenantFinder` (subdomain-based)
- `SwitchTenantDatabaseTask` suggests database-per-tenant but we use shared schema
- `PrefixCacheTask` and `SwitchRouteCacheTask` configured
- `queues.tenant_key` = `organization_id` for queue tagging

---

## Routes Configuration

**File**: `routes/api.php`

```php
Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'status' => 'ok', 'service' => 'flowos-api',
    ]))->name('health');
    // Other routes commented out
});
```

**Issues**:
- No tenant middleware applied to routes
- Only health endpoint exists
- No tenant middleware applied to API routes

---

## Spatie Multitenancy Package Audit

**Package**: `spatie/laravel-multitenancy` v3.x

**Current Usage**:
- ✅ Tenant model: `Organization`
- ✅ Domain tenant finder
- ✅ Cache prefixing via `PrefixCacheTask`
- ✅ Queue tenant awareness via `tenant_key`
- ❌ `SwitchTenantDatabaseTask` - implies database-per-tenant (not our architecture)
- ❌ No global scopes on models
- ❌ No tenant middleware on routes
- ❌ No user membership verification

**Verdict**: Package is partially configured but not fully utilized. The `SwitchTenantDatabaseTask` suggests database-per-tenant architecture which conflicts with our shared-schema approach.

---

## Database Schema Audit

### Current Tenant Tables (from migrations)

| Table | PK | Tenancy | FK | Soft Deletes | Sync Fields |
|-------|----|---------|----|--------------|-------------|
| organizations | UUIDv7 | Root tenant | - | No | - |
| branches | UUIDv7 | `organization_id` FK | No | No |
| users | UUIDv7 | `organization_id` FK | Soft delete | - |
| personal_access_tokens | UUID | Polymorphic | - | - |
| password_reset_tokens | email (PK) | - | - | - |
| failed_jobs | BIGSERIAL | - | - | - |
| jobs | BIGSERIAL | - | - | - |

### Issues Found

1. **No global scopes** on `User`, `Branch` models
2. **No tenant middleware** on API routes
3. **No user membership verification** in `TenantMiddleware`
4. **Organization model** uses database-per-tenant traits but we use shared schema
5. **No tenant middleware** on API routes
6. **No tenant resolution** from JWT claims or session
7. **No tenant context clearing** in middleware
8. **No cross-tenant reference protection** (CHECK constraints)
9. **No tenant-scoped unique constraints** documented
9. **No tenant middleware** on API routes

---

## Gap Analysis

| Requirement | Current | Target | Gap |
|-------------|---------|--------|-----|
| Global scopes on tenant models | ❌ | ✅ Required | High |
| Tenant middleware on routes | ❌ | ✅ Required | High |
| User membership verification | ❌ | ✅ Required | High |
| JWT claim resolution | ❌ | ✅ Planned | Medium |
| Session fallback | ❌ | ✅ Planned | Medium |
| Cross-tenant reference protection | ❌ | ✅ Recommended | Medium |
| Tenant context in jobs | ❌ | ✅ Planned | Medium |
| Cache isolation | Partial | ✅ Configured | Low |
| Queue tenant awareness | ✅ Configured | ✅ Configured | ✅ Done |
| Cache isolation | ✅ Configured | ✅ Configured | ✅ Done |

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Cross-tenant data leak | High | Critical | Global scopes + middleware |
| Admin bypasses tenant scope | Medium | High | Explicit platform admin checks |
| Cache cross-contamination | Low | Medium | Prefix cache keys |
| Job cross-tenant contamination | Medium | High | Explicit org_id in jobs |
| Admin bypasses tenant scope | Medium | High | Explicit platform admin checks |

---

## Recommended Actions (Priority Order)

1. **P0** - Add `BelongsToOrganization` trait to User, Branch models
2. **P0** - Apply tenant middleware to API routes
3. **P0** - Add user membership verification in `TenantMiddleware`
3. **P1** - Add JWT claim and session fallback resolution
4. **P1** - Add tenant context to jobs
4. **P1** - Add cache key prefixing
5. **P2** - Add cross-tenant reference CHECK constraints
5. **P2** - Add tenant lifecycle states (active/suspended/archived)
5. **P2** - Document Spatie package review for continued use
