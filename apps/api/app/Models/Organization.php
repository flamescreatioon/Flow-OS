<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
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
        'name',
        'legal_name',
        'business_type',
        'registration_number',
        'tax_identification_number',
        'slug',
        'timezone',
        'currency',
        'country_code',
        'currency_code',
        'phone',
        'email',
        'address',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'status' => OrganizationStatus::class,
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
