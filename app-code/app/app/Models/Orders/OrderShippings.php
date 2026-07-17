<?php

declare(strict_types=1);

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShippings extends Model
{
    protected $fillable = [
        'order_id',
        'method',
        'code',
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
            'provider_data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Orders, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }
}
