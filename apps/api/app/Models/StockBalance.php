<?php

namespace App\Models;

use App\Domain\Shared\ValueObjects\Quantity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * A derived read model of the stock held for a product at a location.
 *
 * The balance is maintained from the movement records and is never business
 * authority. There is no endpoint that writes it directly.
 */
class StockBalance extends Model
{
    use HasFactory;

    protected $table = 'stock_balances';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'product_id',
        'location_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
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

    public function quantity(): Quantity
    {
        return Quantity::of($this->quantity);
    }

    public function isNegative(): bool
    {
        return $this->quantity < 0;
    }
}
