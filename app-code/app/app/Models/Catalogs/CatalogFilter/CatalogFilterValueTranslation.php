<?php

declare(strict_types=1);

namespace App\Models\Catalogs\CatalogFilter;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $catalog_filter_value_id
 * @property int $language_id
 * @property string $label
 */
class CatalogFilterValueTranslation extends Model
{
    protected $fillable = [
        'catalog_filter_value_id',
        'language_id',
        'label',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'catalog_filter_value_id' => 'integer',
            'language_id' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<CatalogFilterValue, $this>
     * @psalm-return BelongsTo<CatalogFilterValue, self>
     */
    public function filterValue(): BelongsTo
    {
        return $this->belongsTo(CatalogFilterValue::class, 'catalog_filter_value_id');
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
