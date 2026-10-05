<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Ramsey\Uuid\Uuid;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = Uuid::uuid7()->toString();
            }
        });
    }
}
