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
        'slug',
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
     * @return HasMany<CategoryPath>
     */
    public function categoryPaths(): HasMany
    {
        return $this->hasMany(CategoryPath::class);
    }

    /**
     * Get products associated with the category
     *
     * ```
     * // Get all products in a category
     * $category = Category::find(1);
     * $products = $category->products;
     *
     * // Get all categories for a product
     * $product = Product::find(1);
     * $categories = $product->categories;
     *
     * // Width eager loading
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
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param int $language_id
     *
     * @return Collection<Category>
     */
    public function getActiveCategoriesWithDescriptionsByLanguageId(int $language_id): Collection
    {
        return self::query()
            ->with([
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
        return self::query()
            ->with([
                'categoryDescription' => function ($query) use ($language_id) {
                    $query->where('language_id', $language_id);
                }
            ])
            ->where('is_active', true)
            ->find($category_id);
    }

    /**
     * Rebuild category paths for this category
     *
     * @return void
     */
    public function rebuildPaths(): void
    {
        // Removing old paths
        $this->categoryPaths()->delete();

        // Добавляем путь к самому себе
        CategoryPath::create([
            'category_id' => $this->id,
            'path_id'     => $this->id,
            'level'       => 0,
        ]);

        // If there is a parent, copy its paths
        if ($this->parent_id) {
            $parent_paths = CategoryPath::where('category_id', $this->parent_id)->get();

            foreach ($parent_paths as $path) {
                CategoryPath::create([
                    'category_id' => $this->id,
                    'path_id'     => $path->path_id,
                    'level'       => $path->level + 1,
                ]);
            }
        }
    }

    /**
     * Get category level
     *
     * @return int
     */
    public function getLevel(): int
    {
        return $this->categoryPaths()
            ->where('category_id', $this->id)
            ->max('level') ?? 0;
    }

    /**
     * @param int $parent_id
     *
     * @return Collection<Category>
     */
    public function getCategoryByParentId(int $parent_id): Collection
    {
        return self::query()
            ->where('parent_id', $parent_id)
            ->get();
    }

    /**
     * @param int   $language_id
     * @param array $path_ids
     *
     * @return Collection
     */
    public function getActiveCategoryWithDescriptionsByLanguageId(int $language_id, array $path_ids): Collection
    {
        return self::query()
            ->with([
                'categoryDescription' => function ($query) use ($language_id) {
                    $query->where('language_id', $language_id);
                }
            ])
            ->whereIn('id', $path_ids)
            ->get();
    }

    /**
     * @param int $language_id
     *
     * @return Collection
     */
    public function getActiveCategoryWithDescriptionsAndPathByLanguageId(int $language_id): Collection
    {
        return self::query()
            ->with([
                'categoryDescription' => function ($query) use ($language_id) {
                    $query->where('language_id', $language_id);
                },
                'categoryPaths'       => function ($query) {
                    $query->orderBy('level', 'desc');
                }
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
