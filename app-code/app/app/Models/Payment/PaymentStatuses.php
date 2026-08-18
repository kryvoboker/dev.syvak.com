<?php

declare(strict_types=1);

namespace App\Models\Payment;

use App\Exceptions\PaymentStatusInvariantException;
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
    #[\Override]
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::saving(function (PaymentStatuses $payment_status): void {
            if ($payment_status->is_default) {
                if (
                    $payment_status->exists
                    && (bool) $payment_status->getOriginal('is_default')
                    && $payment_status->isDirty('is_active')
                    && $payment_status->is_active === false
                ) {
                    throw new PaymentStatusInvariantException('default_must_be_active');
                }

                static::query()
                    ->whereKeyNot($payment_status->getKey())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                $payment_status->is_active = true;

                return;
            }

            if ($payment_status->exists && (bool) $payment_status->getOriginal('is_default')) {
                if ($payment_status->isDirty('is_default')) {
                    throw new PaymentStatusInvariantException('cannot_unset_default');
                }

                if ($payment_status->isDirty('is_active') && $payment_status->is_active === false) {
                    throw new PaymentStatusInvariantException('default_must_be_active');
                }
            }

            if (! static::query()->where('is_default', true)->exists()) {
                throw new PaymentStatusInvariantException('default_required');
            }
        });

        static::deleting(function (PaymentStatuses $payment_status): void {
            if ($payment_status->is_default) {
                throw new PaymentStatusInvariantException('cannot_delete_default');
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
                'descriptions' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($locale): void {
                    $query
                        ->select([
                            'id',
                            'payment_status_id',
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
     * @phpstan-return HasMany<PaymentStatusDescriptions, $this>
     * @psalm-return HasMany<PaymentStatusDescriptions, self>
     */
    public function descriptions(): HasMany
    {
        return $this->hasMany(PaymentStatusDescriptions::class, 'payment_status_id');
    }

    /**
     * @phpstan-return HasMany<OrderPayments, $this>
     * @psalm-return HasMany<OrderPayments, self>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayments::class, 'payment_status_id');
    }
}
