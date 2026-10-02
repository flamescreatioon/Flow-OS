# Local Storage Strategy

## Overview

Each platform uses appropriate local storage for offline-first operation.

## Platform Comparison

| Platform | Primary Storage | Sync Queue | Key-Value | Secure Storage |
|----------|----------------|------------|-----------|----------------|
| Web | IndexedDB (Dexie) | IndexedDB | localStorage | N/A |
| Mobile | SQLite (sqflite) | SQLite | SharedPreferences | FlutterSecureStorage |
| Desktop | SQLite (EF Core) | SQLite | JSON config | OS Keychain/DPAPI |

## Web: IndexedDB (Dexie.js)

### Schema
```typescript
// lib/db/index.ts
import Dexie, { Table } from 'dexie';
import { Product, Sale, Customer, SyncOperation } from '@/types';

class FlowOSDB extends Dexie {
  products!: Table<Product, UUID>;
  sales!: Table<Sale, UUID>;
  customers!: Table<Customer, UUID>;
  syncQueue!: Table<SyncOperation, UUID>;
  settings!: Table<{ key: string; value: unknown }, string>;

  constructor() {
    super('flowos-web');
    this.version(1).stores({
      products: 'id, organization_id, name, sku, updated_at, synced_at',
      sales: 'id, organization_id, customer_id, total, status, created_at, synced_at',
      customers: 'id, organization_id, name, email, phone, updated_at, synced_at',
      syncQueue: 'id, entity_type, entity_id, status, created_at',
      settings: 'key',
    });

    this.version(2).stores({
      // Add indexes for common queries
      products: 'id, organization_id, name, sku, updated_at, synced_at, branch_id',
      sales: 'id, organization_id, customer_id, total, status, created_at, synced_at, branch_id',
    }).upgrade(tx => {
      // Migration logic
    });
  }
}

export const db = new FlowOSDB();
```

### Usage Patterns

```typescript
// Read (instant)
export async function getProducts(organizationId: UUID): Promise<Product[]> {
  return db.products
    .where('organization_id')
    .equals(organizationId)
    .toArray();
}

// Write (optimistic)
export async function createProduct(product: Product): Promise<Product> {
  await db.products.put(product);

  // Queue for sync
  await enqueueSync({
    entityType: 'products',
    entityId: product.id,
    operation: 'CREATE',
    payload: product,
  });

  return product;
}

// Sync metadata
export async function markSynced(entityType: string, entityId: UUID, serverVersion: Entity) {
  await db[entityType].update(entityId, {
    ...serverVersion,
    synced_at: new Date().toISOString(),
  });
}

export async function getLastSyncAt(): Promise<Date | null> {
  const setting = await db.settings.get('last_sync_at');
  return setting ? new Date(setting.value as string) : null;
}

export async function setLastSyncAt(date: Date): Promise<void> {
  await db.settings.put({ key: 'last_sync_at', value: date.toISOString() });
}
```

### Performance

```typescript
// Batch operations
export async function bulkUpsertProducts(products: Product[]): Promise<void> {
  await db.transaction('rw', db.products, async () => {
    await db.products.bulkPut(products);
  });
}

// Cursor for large datasets
export async function* streamProducts(organizationId: UUID): AsyncGenerator<Product> {
  let cursor = await db.products
    .where('organization_id')
    .equals(organizationId)
    .eachPrimaryKey();

  for (const id of cursor) {
    const product = await db.products.get(id);
    if (product) yield product;
  }
}
```

## Mobile: SQLite (sqflite)

### Schema
```dart
// lib/core/database/schema.dart

class DatabaseSchema {
  static const int version = 1;

  static Future<void> onCreate(Database db, int version) async {
    await db.execute('''
      CREATE TABLE organizations (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        slug TEXT NOT NULL,
        timezone TEXT NOT NULL DEFAULT 'Africa/Lagos',
        currency TEXT NOT NULL DEFAULT 'NGN',
        settings TEXT,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        synced_at TEXT
      )
    ''');

    await db.execute('''
      CREATE TABLE products (
        id TEXT PRIMARY KEY,
        organization_id TEXT NOT NULL,
        branch_id TEXT,
        name TEXT NOT NULL,
        sku TEXT NOT NULL,
        description TEXT,
        price_in_minor_units INTEGER NOT NULL,
        currency TEXT NOT NULL DEFAULT 'NGN',
        cost_in_minor_units INTEGER,
        stock_quantity INTEGER NOT NULL DEFAULT 0,
        min_stock_level INTEGER NOT NULL DEFAULT 0,
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        synced_at TEXT,
        FOREIGN KEY (organization_id) REFERENCES organizations (id)
      )
    ''');

    await db.execute('''
      CREATE INDEX idx_products_org ON products(organization_id);
      CREATE INDEX idx_products_sku ON products(organization_id, sku);
      CREATE INDEX idx_products_active ON products(is_active);
    ''');

    await db.execute('''
      CREATE TABLE sync_queue (
        id TEXT PRIMARY KEY,
        entity_type TEXT NOT NULL,
        entity_id TEXT NOT NULL,
        operation TEXT NOT NULL,
        payload TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'pending',
        retry_count INTEGER NOT NULL DEFAULT 0,
        error_message TEXT,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
      )
    ''');

    await db.execute('''
      CREATE INDEX idx_sync_queue_status ON sync_queue(status);
      CREATE INDEX idx_sync_queue_entity ON sync_queue(entity_type, entity_id);
    ''');

    // Settings table
    await db.execute('''
      CREATE TABLE settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL
      )
    ''');
  }

  static Future<void> onUpgrade(Database db, int oldVersion, int newVersion) async {
    // Handle migrations
    if (oldVersion < 2) {
      await db.execute('ALTER TABLE products ADD COLUMN barcode TEXT');
    }
  }
}
```

### Repository Pattern
```dart
// lib/core/database/repositories/product_repository.dart

class ProductRepository {
  final Database _db;

  ProductRepository(this._db);

  Future<List<Product>> getByOrganization(String organizationId) async {
    final maps = await _db.query(
      'products',
      where: 'organization_id = ? AND is_active = 1',
      whereArgs: [organizationId],
      orderBy: 'name ASC',
    );
    return maps.map((m) => Product.fromMap(m)).toList();
  }

  Future<Product?> getById(String id) async {
    final maps = await _db.query(
      'products',
      where: 'id = ?',
      whereArgs: [id],
      limit: 1,
    );
    return maps.isNotEmpty ? Product.fromMap(maps.first) : null;
  }

  Future<void> upsert(Product product) async {
    await _db.insert(
      'products',
      product.toMap(),
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<void> delete(String id) async {
    await _db.delete('products', where: 'id = ?', whereArgs: [id]);
  }

  Future<void> markSynced(String id, Product serverVersion) async {
    await _db.update(
      'products',
      serverVersion.toMap()..['synced_at'] = DateTime.now().toIso8601String(),
      where: 'id = ?',
      whereArgs: [id],
    );
  }
}
```

### Sync Queue
```dart
// lib/core/database/repositories/sync_queue_repository.dart

class SyncQueueRepository {
  final Database _db;

  SyncQueueRepository(this._db);

  Future<void> enqueue({
    required String entityType,
    required String entityId,
    required String operation,
    required Map<String, dynamic> payload,
  }) async {
    await _db.insert('sync_queue', {
      'id': Uuid().v4(),
      'entity_type': entityType,
      'entity_id': entityId,
      'operation': operation,
      'payload': jsonEncode(payload),
      'status': 'pending',
      'retry_count': 0,
      'created_at': DateTime.now().toIso8601String(),
      'updated_at': DateTime.now().toIso8601String(),
    });
  }

  Future<List<SyncOperation>> getPending({int limit = 100}) async {
    final maps = await _db.query(
      'sync_queue',
      where: 'status = ?',
      whereArgs: ['pending'],
      orderBy: 'created_at ASC',
      limit: limit,
    );
    return maps.map((m) => SyncOperation.fromMap(m)).toList();
  }

  Future<void> markSynced(String id) async {
    await _db.update(
      'sync_queue',
      {
        'status': 'synced',
        'updated_at': DateTime.now().toIso8601String(),
      },
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  Future<void> markFailed(String id, String error) async {
    await _db.update(
      'sync_queue',
      {
        'status': 'failed',
        'error_message': error,
        'retry_count': _incrementRetryCount(id),
        'updated_at': DateTime.now().toIso8601String(),
      },
      where: 'id = ?',
      whereArgs: [id],
    );
  }
}
```

## Desktop: SQLite (Entity Framework Core)

### DbContext
```csharp
// src/FlowOS.Infrastructure/Persistence/FlowOSDbContext.cs

public class FlowOSDbContext : DbContext {
    public DbSet<Organization> Organizations => Set<Organization>();
    public DbSet<Branch> Branches => Set<Branch>();
    public DbSet<User> Users => Set<User>();
    public DbSet<Product> Products => Set<Product>();
    public DbSet<Sale> Sales => Set<Sale>();
    public DbSet<SyncOperation> SyncQueue => Set<SyncOperation>();

    protected override void OnConfiguring(DbContextOptionsBuilder options) {
        var dbPath = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "FlowOS",
            "flowos.db"
        );

        Directory.CreateDirectory(Path.GetDirectoryName(dbPath)!);
        options.UseSqlite($"Data Source={dbPath}");

        #if DEBUG
        options.EnableSensitiveDataLogging();
        #endif
    }

    protected override void OnModelCreating(ModelBuilder modelBuilder) {
        // UUIDv7 as primary key
        foreach (var entityType in modelBuilder.Model.GetEntityTypes()) {
            var idProperty = entityType.FindProperty("Id");
            if (idProperty != null && idProperty.ClrType == typeof(string)) {
                idProperty.ValueGenerated = ValueGenerated.Never;
            }
        }

        // Sync queue
        modelBuilder.Entity<SyncOperation>(entity => {
            entity.HasKey(e => e.Id);
            entity.HasIndex(e => e.Status);
            entity.HasIndex(e => new { e.EntityType, e.EntityId });
            entity.Property(e => e.Payload).HasColumnType("TEXT");
        });

        // Settings
        modelBuilder.Entity<Setting>(entity => {
            entity.HasKey(e => e.Key);
            entity.Property(e => e.Value).HasColumnType("TEXT");
        });

        base.OnModelCreating(modelBuilder);
    }
}

// Settings entity
public class Setting {
    public string Key { get; set; } = null!;
    public string Value { get; set; } = null!;
}
```

### Repository
```csharp
// src/FlowOS.Infrastructure/Persistence/Repositories/ProductRepository.cs

public class ProductRepository : IProductRepository {
    private readonly FlowOSDbContext _context;

    public ProductRepository(FlowOSDbContext context) {
        _context = context;
    }

    public async Task<Product?> GetByIdAsync(string id, CancellationToken ct = default) {
        return await _context.Products
            .AsNoTracking()
            .FirstOrDefaultAsync(p => p.Id == id, ct);
    }

    public async Task<IReadOnlyList<Product>> GetByOrganizationAsync(
        string organizationId,
        CancellationToken ct = default
    ) {
        return await _context.Products
            .AsNoTracking()
            .Where(p => p.OrganizationId == organizationId && p.IsActive)
            .OrderBy(p => p.Name)
            .ToListAsync(ct);
    }

    public async Task AddAsync(Product product, CancellationToken ct = default) {
        _context.Products.Add(product);
        await _context.SaveChangesAsync(ct);
    }

    public async Task UpdateAsync(Product product, CancellationToken ct = default) {
        _context.Products.Update(product);
        await _context.SaveChangesAsync(ct);
    }

    public async Task MarkSyncedAsync(string id, Product serverVersion, CancellationToken ct = default) {
        var local = await _context.Products.FindAsync([id], ct);
        if (local != null) {
            _context.Entry(local).CurrentValues.SetValues(serverVersion);
            local.SyncedAt = DateTime.UtcNow;
            await _context.SaveChangesAsync(ct);
        }
    }
}
```

### Sync Queue
```csharp
// src/FlowOS.Infrastructure/Persistence/Repositories/SyncQueueRepository.cs

public class SyncQueueRepository : ISyncQueueRepository {
    private readonly FlowOSDbContext _context;

    public async Task EnqueueAsync(
        string entityType,
        string entityId,
        string operation,
        object payload,
        CancellationToken ct = default
    ) {
        var syncOp = new SyncOperation {
            Id = UuidV7.NewGuid().ToString(),
            EntityType = entityType,
            EntityId = entityId,
            Operation = operation,
            Payload = JsonSerializer.Serialize(payload),
            Status = SyncStatus.Pending,
            RetryCount = 0,
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow,
        };

        _context.SyncQueue.Add(syncOp);
        await _context.SaveChangesAsync(ct);
    }

    public async Task<IReadOnlyList<SyncOperation>> GetPendingAsync(
        int limit = 100,
        CancellationToken ct = default
    ) {
        return await _context.SyncQueue
            .Where(s => s.Status == SyncStatus.Pending)
            .OrderBy(s => s.CreatedAt)
            .Take(limit)
            .ToListAsync(ct);
    }

    public async Task MarkSyncedAsync(string id, CancellationToken ct = default) {
        var op = await _context.SyncQueue.FindAsync([id], ct);
        if (op != null) {
            op.Status = SyncStatus.Synced;
            op.UpdatedAt = DateTime.UtcNow;
            await _context.SaveChangesAsync(ct);
        }
    }

    public async Task MarkFailedAsync(string id, string error, CancellationToken ct = default) {
        var op = await _context.SyncQueue.FindAsync([id], ct);
        if (op != null) {
            op.Status = SyncStatus.Failed;
            op.ErrorMessage = error;
            op.RetryCount++;
            op.UpdatedAt = DateTime.UtcNow;
            await _context.SaveChangesAsync(ct);
        }
    }
}
```

## Secure Storage

### Web (Not Available)
- Use `localStorage` for non-sensitive
- Send tokens via HttpOnly cookies
- Never store secrets in IndexedDB

### Mobile (FlutterSecureStorage)
```dart
// lib/core/storage/secure_storage.dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorage {
  static const _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock_this_device),
  );

  static Future<void> write(String key, String value) =>
    _storage.write(key: key, value: value);

  static Future<String?> read(String key) => _storage.read(key: key);

  static Future<void> delete(String key) => _storage.delete(key: key);

  static Future<void> deleteAll() => _storage.deleteAll();

  // Keys
  static const String authToken = 'auth_token';
  static const String refreshToken = 'refresh_token';
  static const String biometricKey = 'biometric_key';
}
```

### Desktop (OS Keychain/DPAPI)
```csharp
// src/FlowOS.Infrastructure/Services/SecureStorage.cs

public interface ISecureStorage {
    Task<string?> GetAsync(string key);
    Task SetAsync(string key, string value);
    Task DeleteAsync(string key);
}

public class SecureStorage : ISecureStorage {
    // Windows: DPAPI via ProtectedData
    // Linux/macOS: libsecret / Keychain

    public async Task<string?> GetAsync(string key) {
        var data = ProtectedData.Unprotect(
            File.ReadAllBytes(GetPath(key)),
            null,
            DataProtectionScope.CurrentUser
        );
        return Encoding.UTF8.GetString(data);
    }

    public async Task SetAsync(string key, string value) {
        var data = ProtectedData.Protect(
            Encoding.UTF8.GetBytes(value),
            null,
            DataProtectionScope.CurrentUser
        );
        File.WriteAllBytes(GetPath(key), data);
    }

    private string GetPath(string key) {
        var dir = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "FlowOS",
            "secrets"
        );
        Directory.CreateDirectory(dir);
        return Path.Combine(dir, $"{key}.dat");
    }
}
```

## Migration Strategy

### Version Tracking
```typescript
// Web - Dexie versioning
db.version(3).stores({
  products: 'id, organization_id, name, sku, updated_at, synced_at, barcode',
}).upgrade(tx => {
  // Add barcode column
  return tx.table('products').toCollection().modify(p => {
    p.barcode = p.barcode || null;
  });
});
```

```dart
// Mobile - sqflite onUpgrade
static Future<void> onUpgrade(Database db, int oldVersion, int newVersion) async {
  if (oldVersion < 2) {
    await db.execute('ALTER TABLE products ADD COLUMN barcode TEXT');
  }
  if (oldVersion < 3) {
    await db.execute('CREATE TABLE categories (id TEXT PRIMARY KEY, name TEXT)');
  }
}
```

```csharp
// Desktop - EF Core Migrations
// dotnet ef migrations add AddBarcodeToProducts
// dotnet ef database update
```

## Backup & Restore

### Export
```typescript
// Web - Export all data
export async function exportData(): Promise<string> {
  const data = {
    products: await db.products.toArray(),
    sales: await db.sales.toArray(),
    customers: await db.customers.toArray(),
    settings: await db.settings.toArray(),
    exportedAt: new Date().toISOString(),
    version: 1,
  };
  return JSON.stringify(data, null, 2);
}
```

### Import
```typescript
// Web - Import data
export async function importData(json: string): Promise<void> {
  const data = JSON.parse(json);

  await db.transaction('rw', db.products, db.sales, db.customers, async () => {
    await db.products.bulkPut(data.products);
    await db.sales.bulkPut(data.sales);
    await db.customers.bulkPut(data.customers);
    await db.settings.bulkPut(data.settings);
  });
}
```

## Storage Limits

| Platform | Limit | Handling |
|----------|-------|----------|
| IndexedDB | ~50-80% disk | QuotaExceededError, prompt user |
| SQLite (mobile) | Disk space | Monitor, warn at 80% |
| SQLite (desktop) | Disk space | Monitor, warn at 80% |
| localStorage | 5-10 MB | Use only for settings |
| SecureStorage | Small | Only tokens/secrets |

## Monitoring

```typescript
// Storage usage reporting
export async function getStorageInfo(): Promise<StorageInfo> {
  if ('storage' in navigator && 'estimate' in navigator.storage) {
    const estimate = await navigator.storage.estimate();
    return {
      used: estimate.usage || 0,
      available: estimate.quota || 0,
      percentage: (estimate.usage || 0) / (estimate.quota || 1) * 100,
    };
  }
  return { used: 0, available: 0, percentage: 0 };
}
```
