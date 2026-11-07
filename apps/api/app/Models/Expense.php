<?php

namespace App\Models;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * An operational expense record (ADR 0039).
 *
 * A recorded expense is editable; void is terminal and the record is kept as
 * evidence. Expenses never touch stock or the ledger — they are posting
 * inputs for a future accounting integration.
 */
class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'expense_category_id',
        'expense_number',
        'document_number',
        'status',
        'voided_at',
        'description',
        'amount',
        'spent_on',
        'payee',
        'payment_method',
        'note',
    ];

    protected $casts = [
        'status' => ExpenseStatus::class,
        'payment_method' => PaymentMethod::class,
        'voided_at' => 'datetime',
        'spent_on' => 'date',
        'expense_number' => 'integer',
        'amount' => 'integer',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function isRecorded(): bool
    {
        return $this->status === ExpenseStatus::Recorded;
    }

    public function isVoided(): bool
    {
        return $this->status === ExpenseStatus::Voided;
    }

    /**
     * The expense amount as an exact value at the unit price scale.
     */
    public function amount(): UnitPrice
    {
        return UnitPrice::of($this->amount);
    }
}
