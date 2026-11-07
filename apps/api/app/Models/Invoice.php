<?php

namespace App\Models;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\InvoiceStatus;
use App\Enums\TaxMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * A commercial obligation document (ADR 0036).
 *
 * A draft is freely editable and carries no number. Issue snapshots the
 * customer, tax, and every line's commercial values, assigns the sequential
 * document number, and freezes the document. Void is terminal. An invoice
 * has no stock effect and no payment state in this slice.
 */
class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'customer_id',
        'customer_name',
        'status',
        'issued_at',
        'voided_at',
        'due_on',
        'currency',
        'tax_rate_percent',
        'tax_mode',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'note',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'tax_mode' => TaxMode::class,
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
        'due_on' => 'date',
        'invoice_number' => 'integer',
        'tax_rate_percent' => 'integer',
        'subtotal' => 'integer',
        'discount_total' => 'integer',
        'tax_total' => 'integer',
        'grand_total' => 'integer',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'entity_id')
            ->where('entity_type', 'invoice');
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function isIssued(): bool
    {
        return $this->status === InvoiceStatus::Issued;
    }

    public function isVoided(): bool
    {
        return $this->status === InvoiceStatus::Voided;
    }

    /**
     * The document grand total as an exact amount at the unit price scale.
     */
    public function grandTotal(): UnitPrice
    {
        return UnitPrice::of($this->grand_total);
    }

    /**
     * The document tax total as an exact amount at the unit price scale.
     */
    public function taxTotal(): UnitPrice
    {
        return UnitPrice::of($this->tax_total);
    }
}
