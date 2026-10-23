<?php

namespace App\Models;

use App\Enums\BranchStatus;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class Branch extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'branches';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country_code',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'status' => BranchStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = Uuid::uuid7()->toString();
            }
            $model->code = strtoupper($model->code);
        });

        static::updating(function (self $model) {
            if ($model->isDirty('code')) {
                $model->code = strtoupper($model->code);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
