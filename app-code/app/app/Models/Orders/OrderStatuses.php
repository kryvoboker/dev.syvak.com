<?php

declare(strict_types=1);

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatuses extends Model
{
    protected $fillable = [
        'code',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<OrderStatusDescriptions, $this>
     */
    public function descriptions(): HasMany
    {
        return $this->hasMany(OrderStatusDescriptions::class, 'order_status_id');
    }

    /**
     * @return HasMany<Orders, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Orders::class, 'order_status_id');
    }

    /**
     * @return HasMany<OrderHistories, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(OrderHistories::class, 'order_status_id');
    }

    /**
     * @return HasMany<OrderHistories, $this>
     */
    public function previousHistories(): HasMany
    {
        return $this->hasMany(OrderHistories::class, 'old_order_status_id');
    }
}
