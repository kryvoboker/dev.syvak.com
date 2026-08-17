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

    /** @return BelongsTo<PromoCode, $this> */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /** @return BelongsTo<Language, $this> */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
