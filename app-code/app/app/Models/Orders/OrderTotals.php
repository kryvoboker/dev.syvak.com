<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Enums\Order\TotalTypesEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property TotalTypesEnum $total_type
 * @property string $name
 * @property float|string $value
 * @property int $sort_order
 */
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
    #[\Override]
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
     * @phpstan-return BelongsTo<Orders, $this>
     * @psalm-return BelongsTo<Orders, self>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }
}
