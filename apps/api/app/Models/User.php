<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Concerns\Searchable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Searchable;

    protected $table = 'users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'mfa_pending_secret',
        'mfa_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => UserStatus::class,
        'mfa_secret' => 'encrypted',
        'mfa_pending_secret' => 'encrypted',
        'mfa_recovery_codes' => 'array',
        'mfa_enrolled_at' => 'datetime',
        'mfa_last_used_counter' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = Uuid::uuid7()->toString();
            }
        });
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function mfaChallenges(): HasMany
    {
        return $this->hasMany(MfaChallenge::class);
    }

    public function hasMfaEnrolled(): bool
    {
        return $this->mfa_enrolled_at !== null && $this->mfa_secret !== null;
    }

    public function hasActiveMembershipIn(Organization|string $organization): bool
    {
        $organizationId = $organization instanceof Organization ? $organization->getKey() : $organization;

        return $this->memberships()
            ->where('status', MembershipStatus::Active)
            ->where('organization_id', $organizationId)
            ->exists();
    }
}
