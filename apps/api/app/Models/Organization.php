<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;
use Spatie\Multitenancy\Models\Tenant;

class Organization extends Tenant
{
    use HasFactory;
    use Searchable;

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

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function unitOfMeasures(): HasMany
    {
        return $this->hasMany(UnitOfMeasure::class);
    }

    public function inventoryLocations(): HasMany
    {
        return $this->hasMany(InventoryLocation::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
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
