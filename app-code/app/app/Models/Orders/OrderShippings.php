<?php

declare(strict_types=1);

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShippings extends Model
{
    protected $fillable = [
        'order_id',
        'method',
        'code',
        'is_cost_enabled',
        'city',
        'city_id',
        'address',
        'delivery_point',
        'delivery_point_id',
        'postcode',
        'provider_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'is_cost_enabled' => 'boolean',
            'provider_data' => 'array',
        ];
    }

    /**
     * @return Attribute
     */
    public function providerData(): Attribute
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
}
