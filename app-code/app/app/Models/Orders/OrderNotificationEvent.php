<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Enums\Order\OrderNotificationEventStatusEnum;
use App\Enums\Order\OrderNotificationOutcomeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $event_id
 * @property int $order_id
 * @property OrderNotificationOutcomeEnum $outcome
 * @property OrderNotificationEventStatusEnum $status
 * @property int $schema_version
 * @property array<string, mixed> $payload
 * @property int $attempts
 */
class OrderNotificationEvent extends Model
{
    protected $fillable = [
        'event_id',
        'order_id',
        'outcome',
        'schema_version',
        'payload',
        'status',
        'attempts',
        'last_error',
        'published_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'outcome' => OrderNotificationOutcomeEnum::class,
            'status' => OrderNotificationEventStatusEnum::class,
            'schema_version' => 'integer',
            'payload' => 'array',
            'attempts' => 'integer',
            'published_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Orders, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }

    /** @return HasMany<OrderNotificationDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(OrderNotificationDelivery::class);
    }
}
