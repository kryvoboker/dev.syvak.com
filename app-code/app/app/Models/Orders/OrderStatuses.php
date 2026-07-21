<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Exceptions\OrderStatusInvariantException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatuses extends Model
{
    protected $fillable = [
        'code',
        'is_default',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OrderStatuses $order_status): void {
            if ($order_status->is_default) {
                if (
                    $order_status->exists
                    && (bool) $order_status->getOriginal('is_default')
                    && $order_status->isDirty('is_active')
                    && $order_status->is_active === false
                ) {
                    throw new OrderStatusInvariantException('default_must_be_active');
                }

                static::query()
                    ->whereKeyNot($order_status->getKey())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                $order_status->is_active = true;

                return;
            }

            if ($order_status->exists && (bool) $order_status->getOriginal('is_default')) {
                if ($order_status->isDirty('is_default')) {
                    throw new OrderStatusInvariantException('cannot_unset_default');
                }

                if ($order_status->isDirty('is_active') && $order_status->is_active === false) {
                    throw new OrderStatusInvariantException('default_must_be_active');
                }
            }

            if (! static::query()->where('is_default', true)->exists()) {
                throw new OrderStatusInvariantException('default_required');
            }
        });

        static::deleting(function (OrderStatuses $order_status): void {
            if ($order_status->is_default) {
                throw new OrderStatusInvariantException('cannot_delete_default');
            }
        });
    }

    /**
     * @return self|null
     */
    public function getDefaultActiveStatus(): ?self
    {
        $locale = app()->getLocale();

        return self::query()
            ->with([
                'descriptions' => function ($query) use ($locale): void {
                    $query
                        ->select([
                            'id',
                            'order_status_id',
                            'language_id',
                            'name',
                        ])
                        ->whereHas('language', function ($language_query) use ($locale): void {
                            $language_query->where('code', $locale);
                        });
                },
            ])

            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
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
