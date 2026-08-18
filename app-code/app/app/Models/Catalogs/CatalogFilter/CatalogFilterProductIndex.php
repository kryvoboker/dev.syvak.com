<?php

declare(strict_types=1);

namespace App\Models\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterContextTypeEnum;
use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Products\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogFilterProductIndex extends Model
{
    protected $table = 'catalog_filter_product_index';

    protected $fillable = [
        'catalog_filter_set_id',
        'index_version',
        'context_type',
        'category_id',
        'product_id',
        'catalog_filter_group_id',
        'catalog_filter_value_id',
        'attribute_id',
        'base_price',
        'discount_price',
        'effective_price',
        'stock_quantity',
        'is_in_stock',
        'is_active_product',
        'indexed_at',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'catalog_filter_set_id' => 'integer',
            'index_version' => 'integer',
            'context_type' => CatalogFilterContextTypeEnum::class,
            'category_id' => 'integer',
            'product_id' => 'integer',
            'catalog_filter_group_id' => 'integer',
            'catalog_filter_value_id' => 'integer',
            'attribute_id' => 'integer',
            'base_price' => 'decimal:4',
            'discount_price' => 'decimal:4',
            'effective_price' => 'decimal:4',
            'stock_quantity' => 'integer',
            'is_in_stock' => 'boolean',
            'is_active_product' => 'boolean',
            'indexed_at' => 'datetime',
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
     * @phpstan-return BelongsTo<CatalogFilterGroup, $this>
     * @psalm-return BelongsTo<CatalogFilterGroup, self>
     */
    public function filterGroup(): BelongsTo
    {
        return $this->belongsTo(CatalogFilterGroup::class, 'catalog_filter_group_id');
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
     * @phpstan-return BelongsTo<Category, $this>
     * @psalm-return BelongsTo<Category, self>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @phpstan-return BelongsTo<Product, $this>
     * @psalm-return BelongsTo<Product, self>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @phpstan-return BelongsTo<Attribute, $this>
     * @psalm-return BelongsTo<Attribute, self>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
