<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Categories;

use App\Models\Catalogs\Products\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'sort_order',
        'is_active',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'parent_id'  => 'integer',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    /**
     * @return HasMany<CategoryDescription>
     */
    public function categoryDescription(): HasMany
    {
        return $this->hasMany(CategoryDescription::class);
    }

    /**
     * @return HasMany<CategoryImage>
     */
    public function categoryImage(): HasMany
    {
        return $this->hasMany(CategoryImage::class);
    }

    /**
     * Get products associated with the category
     *
     * ```
     * // Получить все товары категории
     * $category = Category::find(1);
     * $products = $category->products;
     *
     * // Получить все категории товара
     * $product = Product::find(1);
     * $categories = $product->categories;
     *
     * // С eager loading
     * $category = Category::with('products')->find(1);
     * $product = Product::with('categories')->find(1);
     * ```
     *
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'category_product',
            'category_id',
            'product_id'
        )->withTimestamps();
    }

    /**
     * @param int $language_id
     *
     * @return Collection<Category>
     */
    public function getActiveCategoriesWithDescriptionsByLanguageId(int $language_id): Collection
    {
        return self::with([
            'categoryDescription' => function ($query) use ($language_id) {
                $query->where('language_id', $language_id);
            }
        ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param int $category_id
     * @param int $language_id
     *
     * @return self|null
     */
    public function getActiveCategoryWithDescriptionByCategoryIdAndLanguageId(int $category_id, int $language_id): ?self
    {
        return self::with([
            'categoryDescription' => function ($query) use ($language_id) {
                $query->where('language_id', $language_id);
            }
        ])
            ->where('is_active', true)
            ->find($category_id);
    }
}
