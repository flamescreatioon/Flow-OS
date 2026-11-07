<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

/**
 * One tracked delivery attempt (ADR 0037).
 *
 * The row is created with the send request and updated by the queued
 * delivery job. It records what was attempted and what happened — it is
 * not business truth and never mutates the entity it points at.
 */
class NotificationDelivery extends Model
{
    use HasFactory;

    protected $table = 'notification_deliveries';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'entity_type',
        'entity_id',
        'channel',
        'driver',
        'recipient',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'channel' => NotificationChannel::class,
        'status' => NotificationDeliveryStatus::class,
        'sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = Uuid::uuid7()->toString();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
