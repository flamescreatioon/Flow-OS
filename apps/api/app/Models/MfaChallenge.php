<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class MfaChallenge extends Model
{
    use HasFactory;

    protected $table = 'mfa_challenges';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'challenge_hash',
        'purpose',
        'client_name',
        'attempts',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
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

    /**
     * A challenge may be redeemed once, and only before it expires.
     */
    public function isRedeemable(): bool
    {
        return $this->consumed_at === null && ! $this->expires_at->isPast();
    }
}
