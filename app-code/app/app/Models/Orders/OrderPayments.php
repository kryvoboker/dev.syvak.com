<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Models\Payment\PaymentStatuses;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayments extends Model
{
    protected $fillable = [
        'order_id',
        'method',
        'code',
        'payment_status_id',
        'transaction_id',
        'amount',
        'failure_reason',
        'provider_data',
        'paid_at',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'payment_status_id' => 'integer',
            'amount' => 'decimal:4',
            'provider_data' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute
     */
    /** @return Attribute<mixed, mixed> */
    public function providerData(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : null,
        );
    }

    /**
     * @return BelongsTo<Orders, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }

    /**
     * @return BelongsTo<PaymentStatuses, $this>
     */
    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatuses::class, 'payment_status_id');
    }
}
