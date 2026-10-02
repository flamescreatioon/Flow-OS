# Audit Fields

## Standard Audit Columns

| Column | Type | Purpose |
|--------|------|---------|
| `created_at` | `TIMESTAMPTZ` | Record creation (immutable) |
| `updated_at` | `TIMESTAMPTZ` | Last modification (auto-updated) |
| `deleted_at` | `TIMESTAMPTZ` | Soft delete timestamp (NULL = active) |
| `created_by` | `UUID` | User who created (FK to users) |
| `updated_by` | `UUID` | User who last modified |
| `deleted_by` | `UUID` | User who soft-deleted |

## Implementation

### Migration
```php
$table->timestampsTz();           // created_at, updated_at
$table->softDeletesTz('deleted_at'); // deleted_at

// Optional audit trail
$table->foreignUuid('created_by')->nullable()->constrained('users');
$table->foreignUuid('updated_by')->nullable()->constrained('users');
$table->foreignUuid('deleted_by')->nullable()->constrained('users');
```

### Laravel Model
```php
protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
];

// Auto-set created_by/updated_by via observer
protected static function booted(): void {
    static::creating(function ($model) {
        $model->created_by = auth()->id();
        $model->updated_by = auth()->id();
    });

    static::updating(function ($model) {
        $model->updated_by = auth()->id();
    });

    static::deleting(function ($model) {
        $model->deleted_by = auth()->id();
    });
}
```

## Sync Metadata (Offline-First)

For entities that sync between offline clients and server:

```sql
-- Sync metadata columns
synced_at TIMESTAMPTZ,           -- Last successful sync
server_updated_at TIMESTAMPTZ,   -- Server's updated_at at last sync
device_id UUID,                  -- Originating device
version BIGINT DEFAULT 1,        -- Optimistic locking
```

### Laravel
```php
$table->timestampTz('synced_at')->nullable();
$table->timestampTz('server_updated_at')->nullable();
$table->uuid('device_id')->nullable();
$table->bigInteger('version')->default(1);
```

### Laravel Sync Trait

```php
trait Syncable {
    public function scopePendingSync($query) {
        return $query->where('synced_at', '<', 'updated_at')
                     ->orWhereNull('synced_at');
    }

    public function markSynced(): void {
        $this->update(['synced_at' => now()]);
    }
}
```

## Sync Conflict Resolution

```
Client                    Server
   │                        │
   ├── Write locally ──────►│
   │                        │
   │◄──── ACK ──────────────┤
   │                        │
   ├── (offline edits)      │
   │                        │
   ├── Sync ───────────────►│
   │                        ├── Conflict?
   │                        │   ├─ Yes → Resolve (server wins / merge)
   │                        │   └─ No  ──► Apply, update synced_at
   │                        │
   │◄──── ACK ──────────────┤
```

## Sync Queue Table

```sql
CREATE TABLE sync_queue (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v7(),
    entity_type VARCHAR(100) NOT NULL,
    entity_id UUID NOT NULL,
    operation VARCHAR(10) NOT NULL, -- CREATE, UPDATE, DELETE
    payload JSONB NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```
