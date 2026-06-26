<?php

declare(strict_types=1);

namespace App\Models\Catalogs\CatalogFilter;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    protected function casts(): array
    {
        return [
            'catalog_filter_value_id' => 'integer',
            'language_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CatalogFilterValue, $this>
     */
    public function filterValue(): BelongsTo
    {
        return $this->belongsTo(CatalogFilterValue::class, 'catalog_filter_value_id');
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
