<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_status_id
 * @property int $language_id
 * @property string $name
 */
class OrderStatusDescriptions extends Model
{
    protected $fillable = [
        'order_status_id',
        'language_id',
        'name',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'order_status_id' => 'integer',
            'language_id' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<OrderStatuses, $this>
     * @psalm-return BelongsTo<OrderStatuses, self>
     */
    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatuses::class, 'order_status_id');
    }

    /**
     * @phpstan-return BelongsTo<Language, $this>
     * @psalm-return BelongsTo<Language, self>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
