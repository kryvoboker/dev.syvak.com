<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Enums\Cart\CartModeEnum;
use App\Models\ApplicationSettings\Currency;
use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/** @property CartModeEnum $order_type */
class Orders extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'order_status_id',
        'order_status_name',
        'order_type',
        'comment',
        'total',
        'language_id',
        'language_code',
        'currency_id',
        'currency_code',
        'exchange_rate',
        'accept_language',
        'ip',
        'forwarded_ip',
        'user_agent',
        'added_at',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'order_status_id' => 'integer',
            'order_type' => CartModeEnum::class,
            'total' => 'decimal:4',
            'language_id' => 'integer',
            'currency_id' => 'integer',
            'exchange_rate' => 'decimal:8',
            'added_at' => 'datetime',
        ];
    }

    /**
     * @phpstan-return BelongsTo<OrderStatuses, $this>
     * @psalm-return BelongsTo<OrderStatuses, self>
     */
    public function status(): BelongsTo
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

    /**
     * @phpstan-return BelongsTo<Currency, $this>
     * @psalm-return BelongsTo<Currency, self>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @phpstan-return HasOne<OrderCustomers, $this>
     * @psalm-return HasOne<OrderCustomers, self>
     */
    public function customer(): HasOne
    {
        return $this->hasOne(OrderCustomers::class, 'order_id');
    }

    /**
     * @phpstan-return HasOne<OrderShippings, $this>
     * @psalm-return HasOne<OrderShippings, self>
     */
    public function shipping(): HasOne
    {
        return $this->hasOne(OrderShippings::class, 'order_id');
    }

    /**
     * @phpstan-return HasMany<OrderPayments, $this>
     * @psalm-return HasMany<OrderPayments, self>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayments::class, 'order_id');
    }

    /**
     * @phpstan-return HasMany<OrderProducts, $this>
     * @psalm-return HasMany<OrderProducts, self>
     */
    public function products(): HasMany
    {
        return $this->hasMany(OrderProducts::class, 'order_id');
    }

    /**
     * @phpstan-return HasMany<OrderTotals, $this>
     * @psalm-return HasMany<OrderTotals, self>
     */
    public function totals(): HasMany
    {
        return $this->hasMany(OrderTotals::class, 'order_id');
    }

    /**
     * @phpstan-return HasMany<OrderHistories, $this>
     * @psalm-return HasMany<OrderHistories, self>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(OrderHistories::class, 'order_id');
    }
}
