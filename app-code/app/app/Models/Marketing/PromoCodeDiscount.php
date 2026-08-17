<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use App\Models\ApplicationSettings\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCodeDiscount extends Model
{
    protected $fillable = [
        'promo_code_id',
        'currency_id',
        'value',
    ];

    protected function casts(): array
    {
        return ['value' => 'decimal:4'];
    }

    /** @return BelongsTo<PromoCode, $this> */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
