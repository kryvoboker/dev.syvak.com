<?php

declare(strict_types=1);

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property string $method
 * @property string $code
 * @property int|null $city_id
 * @property int|null $delivery_point_id
 * @property string|null $address
 * @property bool $is_cost_enabled
 */
/**
 * @property int $id
 * @property int $order_id
 * @property string $method
 * @property string $code
 * @property int|null $city_id
 * @property int|null $delivery_point_id
 * @property string|null $address
 * @property bool $is_cost_enabled
 */
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
    #[\Override]
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'is_cost_enabled' => 'boolean',
            'provider_data' => 'array',
        ];
    }
    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function providerData(): Attribute
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
}
