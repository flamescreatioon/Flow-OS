<?php

namespace App\Models;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\TaxMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * The organization-level tax configuration (ADR 0036).
 *
 * One row per organization. Invoices snapshot the enabled rate and mode at
 * issue time, so changing this row never rewrites issued invoices.
 */
class TaxSetting extends Model
{
    use HasFactory;

    protected $table = 'tax_settings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'tax_enabled',
        'tax_rate_percent',
        'tax_mode',
    ];

    protected $casts = [
        'tax_enabled' => 'boolean',
        'tax_rate_percent' => 'integer',
        'tax_mode' => TaxMode::class,
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

    /**
     * The configured rate as an exact percentage at the unit price scale.
     */
    public function rate(): ?UnitPrice
    {
        return $this->tax_rate_percent === null ? null : UnitPrice::of($this->tax_rate_percent);
    }
}
