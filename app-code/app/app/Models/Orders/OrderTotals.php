<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Enums\Order\TotalTypesEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTotals extends Model
{
    protected $fillable = [
        'order_id',
        'total_type',
        'name',
        'value',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'total_type' => TotalTypesEnum::class,
            'value' => 'decimal:4',
            'sort_order' => 'integer',
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
