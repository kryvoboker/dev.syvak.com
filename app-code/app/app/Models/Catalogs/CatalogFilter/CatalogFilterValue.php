<?php

declare(strict_types=1);

namespace App\Models\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterValueTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $catalog_filter_group_id
 * @property string $code
 * @property CatalogFilterValueTypeEnum $value_type
 * @property string|null $value_string
 * @property string|null $value_number
 * @property string|null $range_from
 * @property string|null $range_to
 * @property bool $is_enabled
 * @property int $sort_order
 * @property int $products_count_cached
 * @property array<string, mixed>|null $meta
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CatalogFilterValueTranslation> $translations
 */
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
    #[\Override]
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
     * @phpstan-return BelongsTo<CatalogFilterGroup, $this>
     * @psalm-return BelongsTo<CatalogFilterGroup, self>
     */
    public function filterGroup(): BelongsTo
    {
        return $this->belongsTo(CatalogFilterGroup::class, 'catalog_filter_group_id');
    }

    /**
     * @phpstan-return HasMany<CatalogFilterValueTranslation, $this>
     * @psalm-return HasMany<CatalogFilterValueTranslation, self>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CatalogFilterValueTranslation::class);
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
