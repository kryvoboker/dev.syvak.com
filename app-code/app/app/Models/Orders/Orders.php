<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Enums\CartModeEnum;
use App\Models\ApplicationSettings\Currency;
use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orders extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'order_status_id',
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
     * @return BelongsTo<OrderStatuses, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatuses::class, 'order_status_id');
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return HasOne<OrderCustomers, $this>
     */
    public function customer(): HasOne
    {
        return $this->hasOne(OrderCustomers::class, 'order_id');
    }

    /**
     * @return HasOne<OrderShippings, $this>
     */
    public function shipping(): HasOne
    {
        return $this->hasOne(OrderShippings::class, 'order_id');
    }

    /**
     * @return HasMany<OrderPayments, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayments::class, 'order_id');
    }

    /**
     * @return HasMany<OrderProducts, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(OrderProducts::class, 'order_id');
    }

    /**
     * @return HasMany<OrderTotals, $this>
     */
    public function totals(): HasMany
    {
        return $this->hasMany(OrderTotals::class, 'order_id');
    }

    /**
     * @return HasMany<OrderHistories, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(OrderHistories::class, 'order_id');
    }
}
