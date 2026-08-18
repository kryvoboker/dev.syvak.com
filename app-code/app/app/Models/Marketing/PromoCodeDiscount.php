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

    /** @phpstan-return BelongsTo<PromoCode, $this>
     * @psalm-return BelongsTo<PromoCode, self>
     */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /** @phpstan-return BelongsTo<Currency, $this>
     * @psalm-return BelongsTo<Currency, self>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
