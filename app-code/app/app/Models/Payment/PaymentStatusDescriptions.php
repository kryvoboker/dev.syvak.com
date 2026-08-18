<?php

declare(strict_types=1);

namespace App\Models\Payment;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentStatusDescriptions extends Model
{
    protected $fillable = [
        'payment_status_id',
        'language_id',
        'name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_status_id' => 'integer',
            'language_id' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<PaymentStatuses, $this>
     * @psalm-return BelongsTo<PaymentStatuses, self>
     */
    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatuses::class, 'payment_status_id');
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
