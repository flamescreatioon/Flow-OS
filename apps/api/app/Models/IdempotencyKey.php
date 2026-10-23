<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class IdempotencyKey extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'organization_id',
        'key',
        'route',
        'request_hash',
        'status',
        'response_code',
        'response_body',
        'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = Uuid::uuid7()->toString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * The key is reserved by an in-flight request that has not recorded a
     * response yet.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }
}
