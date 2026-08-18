<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCodeErrorTranslation extends Model
{
    protected $fillable = [
        'promo_code_id',
        'language_id',
        'expired_message',
        'minimum_order_message',
        'usage_limit_message',
    ];

    /** @phpstan-return BelongsTo<PromoCode, $this>
     * @psalm-return BelongsTo<PromoCode, self>
     */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /** @phpstan-return BelongsTo<Language, $this>
     * @psalm-return BelongsTo<Language, self>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
