<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class Branch extends Model
{
    use HasFactory;

    protected $table = 'branches';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'organization_id',
        'name',
        'address',
        'phone',
        'email',
        'is_main',
    ];

    protected $casts = [
        'is_main' => 'boolean',
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
}
