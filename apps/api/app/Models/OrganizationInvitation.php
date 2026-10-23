<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class OrganizationInvitation extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'organization_id', 'invited_by_user_id', 'token_hash'];

    protected $hidden = ['token_hash'];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = ['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'declined_at' => 'datetime', 'cancelled_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->id ??= Uuid::uuid7()->toString();
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function state(): string
    {
        if ($this->accepted_at) {
            return 'accepted';
        } if ($this->declined_at) {
            return 'declined';
        } if ($this->cancelled_at) {
            return 'cancelled';
        }

        return $this->expires_at?->isPast() ? 'expired' : 'pending';
    }
}
