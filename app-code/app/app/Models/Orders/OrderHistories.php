<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderHistories extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'old_order_status_id',
        'order_status_id',
        'event',
        'json',
        'comment',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'user_id' => 'integer',
            'old_order_status_id' => 'integer',
            'order_status_id' => 'integer',
            'json' => 'array',
        ];
    }

    /**
     * @return Attribute
     */
    public function json(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : null,
        );
    }

    /**
     * @return BelongsTo<Orders, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<OrderStatuses, $this>
     */
    public function oldStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatuses::class, 'old_order_status_id');
    }

    /**
     * @return BelongsTo<OrderStatuses, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatuses::class, 'order_status_id');
    }
}
