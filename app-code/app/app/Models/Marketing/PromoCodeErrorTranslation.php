<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $promo_code_id
 * @property int $language_id
 * @property string|null $expired_message
 * @property string|null $minimum_order_message
 * @property string|null $usage_limit_message
 */
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
