<?php

namespace App\Models;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Shared\ValueObjects\Quantity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * One product line of a goods receipt.
 *
 * The line snapshots the product name and the unit cost so the commercial
 * record never depends on the current catalogue entry. The stock effect of
 * the line lives in the receipt movement that references the receipt.
 */
class GoodsReceiptLine extends Model
{
    use HasFactory;
    protected $table = 'goods_receipt_lines';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'goods_receipt_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'integer',
        'line_total' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = Uuid::uuid7()->toString();
            }
        });
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The received magnitude as an exact quantity.
     */
    public function quantity(): Quantity
    {
        return Quantity::of($this->quantity);
    }

    /**
     * The unit cost as an exact amount at the unit price scale.
     */
    public function unitCost(): UnitPrice
    {
        return UnitPrice::of($this->unit_cost);
    }

    /**
     * The line total as an exact amount at the unit price scale.
     */
    public function lineTotal(): UnitPrice
    {
        return UnitPrice::of($this->line_total);
    }
}
