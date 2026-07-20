<?php

declare(strict_types=1);

namespace App\Models\Payment;

use App\Models\Orders\OrderPayments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentStatuses extends Model
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
        static::saving(function (PaymentStatuses $payment_status): void {
            if (! $payment_status->is_default) {
                return;
            }

            static::query()
                ->whereKeyNot($payment_status->getKey())
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $payment_status->is_active = true;
        });
    }

    public function getDefaultActiveStatus(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    /**
     * @return HasMany<PaymentStatusDescriptions, $this>
     */
    public function descriptions(): HasMany
    {
        return $this->hasMany(PaymentStatusDescriptions::class, 'payment_status_id');
    }

    /**
     * @return HasMany<OrderPayments, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayments::class, 'payment_status_id');
    }
}
