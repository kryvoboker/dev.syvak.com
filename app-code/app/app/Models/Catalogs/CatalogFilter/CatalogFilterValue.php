<?php

declare(strict_types=1);

namespace App\Models\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterValueTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogFilterValue extends Model
{
    protected $fillable = [
        'catalog_filter_group_id',
        'code',
        'value_type',
        'value_string',
        'value_number',
        'range_from',
        'range_to',
        'is_enabled',
        'sort_order',
        'products_count_cached',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'catalog_filter_group_id' => 'integer',
            'value_type' => CatalogFilterValueTypeEnum::class,
            'value_number' => 'decimal:4',
            'range_from' => 'decimal:4',
            'range_to' => 'decimal:4',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'products_count_cached' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CatalogFilterGroup, $this>
     */
    public function filterGroup(): BelongsTo
    {
        return $this->belongsTo(CatalogFilterGroup::class, 'catalog_filter_group_id');
    }

    /**
     * @return HasMany<CatalogFilterValueTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CatalogFilterValueTranslation::class);
    }

    /**
     * @return HasMany<CatalogFilterProductIndex, $this>
     */
    public function indexRows(): HasMany
    {
        return $this->hasMany(CatalogFilterProductIndex::class);
    }
}
