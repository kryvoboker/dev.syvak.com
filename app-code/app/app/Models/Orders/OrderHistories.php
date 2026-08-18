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
    #[\Override]
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
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function json(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : null,
        );
    }

    /**
     * @phpstan-return BelongsTo<Orders, $this>
     * @psalm-return BelongsTo<Orders, self>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }

    /**
     * @phpstan-return BelongsTo<User, $this>
     * @psalm-return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @phpstan-return BelongsTo<OrderStatuses, $this>
     * @psalm-return BelongsTo<OrderStatuses, self>
     */
    public function oldStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatuses::class, 'old_order_status_id');
    }

    /**
     * @phpstan-return BelongsTo<OrderStatuses, $this>
     * @psalm-return BelongsTo<OrderStatuses, self>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatuses::class, 'order_status_id');
    }
}
