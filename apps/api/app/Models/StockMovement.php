<?php

namespace App\Models;

use App\Domain\Shared\ValueObjects\Quantity;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * The authoritative record of a change in stock.
 *
 * A movement is immutable. It is never updated and never deleted. A
 * correction is a new movement that references the movement it reverses.
 */
class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'product_id',
        'location_id',
        'corrects_movement_id',
        'transfer_movement_id',
        'type',
        'quantity',
        'unit_cost',
        'reference',
        'note',
        'occurred_at',
    ];

    protected $casts = [
        'type' => StockMovementType::class,
        'quantity' => 'integer',
        'unit_cost' => 'integer',
        'occurred_at' => 'datetime',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function corrects(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_movement_id');
    }

    public function correctedBy(): HasMany
    {
        return $this->hasMany(self::class, 'corrects_movement_id');
    }

    public function transferPair(): BelongsTo
    {
        return $this->belongsTo(self::class, 'transfer_movement_id');
    }

    /**
     * The signed quantity this movement applied.
     */
    public function quantity(): Quantity
    {
        return Quantity::of($this->quantity);
    }

    public function increasesStock(): bool
    {
        return $this->quantity > 0;
    }

    public function decreasesStock(): bool
    {
        return $this->quantity < 0;
    }
}
