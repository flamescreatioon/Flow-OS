<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * An organization-configurable unit of measure.
 *
 * The precision is the number of decimal places a client may use for
 * quantities of this unit. The stored scale is fixed by the platform and is
 * not affected by this value.
 */
class UnitOfMeasure extends Model
{
    use HasFactory;

    protected $table = 'unit_of_measures';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'precision',
    ];

    protected $casts = [
        'precision' => 'integer',
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
