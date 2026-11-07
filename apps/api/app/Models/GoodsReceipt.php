<?php

namespace App\Models;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\GoodsReceiptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * An immutable commercial record of goods received into a location (ADR 0035).
 *
 * The receipt is finalized on creation: its lines and their stock movements
 * are written in one transaction and the receipt has no update or delete path.
 * Purchasing never writes stock balances; the movements recorded through
 * Inventory are the stock truth.
 */
class GoodsReceipt extends Model
{
    use HasFactory;

    protected $table = 'goods_receipts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'supplier_id',
        'location_id',
        'supplier_name',
        'receipt_number',
        'document_number',
        'status',
        'received_on',
        'note',
        'total_cost',
    ];

    protected $casts = [
        'status' => GoodsReceiptStatus::class,
        'received_on' => 'date',
        'receipt_number' => 'integer',
        'total_cost' => 'integer',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function isRecorded(): bool
    {
        return $this->status === GoodsReceiptStatus::Recorded;
    }

    /**
     * The receipt total as an exact amount at the unit price scale.
     */
    public function totalCost(): UnitPrice
    {
        return UnitPrice::of($this->total_cost);
    }
}
