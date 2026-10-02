<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;
use Spatie\Multitenancy\Models\Tenant;

class Organization extends Tenant
{
    use HasFactory;

    protected $table = 'organizations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'timezone',
        'currency',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = Uuid::uuid7()->toString();
            }
        });
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Legacy relationship retained while users.organization_id remains available.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function getTenantDatabaseName(): string
    {
        return 'flowos_'.$this->id;
    }

    public function getTenantDatabasePrefix(): string
    {
        return '';
    }
}
