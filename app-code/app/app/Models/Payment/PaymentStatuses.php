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
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
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
