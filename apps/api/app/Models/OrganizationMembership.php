<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class OrganizationMembership extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    protected $table = 'organization_memberships';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Membership fields are server-controlled. Application use cases must use
     * explicit assignment rather than mass assignment from request input.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = Uuid::uuid7()->toString();
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
