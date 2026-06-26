<?php

declare(strict_types=1);

namespace App\Models\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogFilterGroup extends Model
{
    protected $fillable = [
        'catalog_filter_set_id',
        'code',
        'source_type',
        'source_id',
        'is_enabled',
        'sort_order',
        'get_key',
        'config',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'catalog_filter_set_id' => 'integer',
            'source_type' => CatalogFilterGroupSourceTypeEnum::class,
            'source_id' => 'integer',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'config' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CatalogFilterSet, $this>
     */
    public function filterSet(): BelongsTo
    {
        return $this->belongsTo(CatalogFilterSet::class, 'catalog_filter_set_id');
    }

    /**
     * @return HasMany<CatalogFilterGroupTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CatalogFilterGroupTranslation::class);
    }

    /**
     * @return HasMany<CatalogFilterValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(CatalogFilterValue::class);
    }

    /**
     * @return HasMany<CatalogFilterProductIndex, $this>
     */
    public function indexRows(): HasMany
    {
        return $this->hasMany(CatalogFilterProductIndex::class);
    }
}
