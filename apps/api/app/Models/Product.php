<?php

namespace App\Models;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\ProductType;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * A catalogue product or service.
 *
 * The catalogue describes what can be bought or sold. It does not own stock
 * quantity, historical sale price, purchase cost, or accounting valuation.
 */
class Product extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'products';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'unit_of_measure_id',
        'category_id',
        'name',
        'sku',
        'type',
        'tax_code',
        'price',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'price' => 'integer',
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

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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

    public function tracksStock(): bool
    {
        return $this->type->tracksStock();
    }

    /**
     * The list price as an exact unit price.
     */
    public function unitPrice(): UnitPrice
    {
        return UnitPrice::of($this->price);
    }
}
