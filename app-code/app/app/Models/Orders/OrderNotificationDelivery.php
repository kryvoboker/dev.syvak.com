<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Enums\Order\OrderNotificationChannelEnum;
use App\Enums\Order\OrderNotificationDeliveryStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_notification_event_id
 * @property OrderNotificationChannelEnum $channel
 * @property OrderNotificationDeliveryStatusEnum $status
 * @property int $attempts
 */
class OrderNotificationDelivery extends Model
{
    protected $fillable = [
        'order_notification_event_id',
        'channel',
        'status',
        'attempts',
        'provider_reference',
        'last_error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => OrderNotificationChannelEnum::class,
            'status' => OrderNotificationDeliveryStatusEnum::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<OrderNotificationEvent, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(OrderNotificationEvent::class, 'order_notification_event_id');
    }
}
