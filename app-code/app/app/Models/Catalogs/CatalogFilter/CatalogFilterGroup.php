<?php

declare(strict_types=1);

namespace App\Models\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property CatalogFilterGroupSourceTypeEnum $source_type */
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
    #[\Override]
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
     * @phpstan-return BelongsTo<CatalogFilterSet, $this>
     * @psalm-return BelongsTo<CatalogFilterSet, self>
     */
    public function filterSet(): BelongsTo
    {
        return $this->belongsTo(CatalogFilterSet::class, 'catalog_filter_set_id');
    }

    /**
     * @phpstan-return HasMany<CatalogFilterGroupTranslation, $this>
     * @psalm-return HasMany<CatalogFilterGroupTranslation, self>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CatalogFilterGroupTranslation::class);
    }

    /**
     * @phpstan-return HasMany<CatalogFilterValue, $this>
     * @psalm-return HasMany<CatalogFilterValue, self>
     */
    public function values(): HasMany
    {
        return $this->hasMany(CatalogFilterValue::class);
    }

    /**
     * @phpstan-return HasMany<CatalogFilterProductIndex, $this>
     * @psalm-return HasMany<CatalogFilterProductIndex, self>
     */
    public function indexRows(): HasMany
    {
        return $this->hasMany(CatalogFilterProductIndex::class);
    }
}
