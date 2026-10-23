<?php

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * A place where stock is held.
 *
 * A location belongs to one organization and may sit inside a branch of that
 * same organization. A location is not interchangeable with a branch.
 */
class InventoryLocation extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'inventory_locations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'code',
        'name',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = Uuid::uuid7()->toString();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelowsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
