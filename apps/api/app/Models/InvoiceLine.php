<?php

namespace App\Models;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Shared\ValueObjects\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * One billed line of an invoice.
 *
 * The description and every commercial value are snapshotted at issue time,
 * so an issued invoice never depends on the current catalogue entry. In
 * inclusive tax mode the tax sits inside line_total; otherwise it is added
 * on top of it.
 */
class InvoiceLine extends Model
{
    protected $table = 'invoice_lines';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'discount' => 'integer',
        'tax_amount' => 'integer',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function quantity(): Quantity
    {
        return Quantity::of($this->quantity);
    }

    public function unitPrice(): UnitPrice
    {
        return UnitPrice::of($this->unit_price);
    }

    public function discount(): UnitPrice
    {
        return UnitPrice::of($this->discount);
    }

    public function taxAmount(): UnitPrice
    {
        return UnitPrice::of($this->tax_amount);
    }

    public function lineTotal(): UnitPrice
    {
        return UnitPrice::of($this->line_total);
    }
}
