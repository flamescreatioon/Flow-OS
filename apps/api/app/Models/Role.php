<?php

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class Role extends Model
{
    use HasFactory;
    use Searchable;

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    public const RESERVED_SLUGS = ['owner', 'admin', 'member'];

    protected $casts = ['is_system' => 'boolean'];

    public static function isReservedSlug(string $nameOrSlug): bool
    {
        return in_array(Str::slug(trim($nameOrSlug)), self::RESERVED_SLUGS, true);
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->id ??= Uuid::uuid7()->toString());
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationMembership::class, 'organization_membership_roles');
    }
}
