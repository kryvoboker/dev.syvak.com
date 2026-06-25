<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Categories;

use App\Models\Catalogs\Products\Product;
use App\Models\Trait\HasSlugsTrait;
use App\Models\Trait\SlugTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasSlugsTrait;
    use SlugTrait;

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
     * @return HasMany<CategoryDescription, $this>
     */
    public function categoryDescription(): HasMany
    {
        return $this->hasMany(CategoryDescription::class);
    }

    /**
     * @return HasMany<CategoryImage, $this>
     */
    public function categoryImage(): HasMany
    {
        return $this->hasMany(CategoryImage::class);
    }

    /**
     * @return HasMany<CategoryPath, $this>
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
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'category_product',
            'category_id',
            'product_id',
        )->withTimestamps();
    }

    /**
     * @return Collection<Category>
     */
    public function getActiveCategoriesWithDescriptionsByLanguageId(int $language_id): Collection
    {
        return self::query()
            ->with([
                'categoryDescription' => function ($query) use ($language_id) {
                    $query->where('language_id', $language_id);
                },
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<Category>
     */
    public function getActiveCategoriesWithDescriptionsAndSlugsByLanguageId(int $language_id): Collection
    {
        return self::query()
            ->with([
                'categoryDescription' => function ($query) use ($language_id) {
                    $query->where('language_id', $language_id);
                },
                'slugs' => function ($query) use ($language_id) {
                    $query->where('language_id', $language_id);
                },
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getActiveCategoryWithDescriptionByCategoryIdAndLanguageId(int $category_id, int $language_id): ?self
    {
        return self::query()
            ->with([
                'categoryDescription' => function ($query) use ($language_id) {
                    $query->where('language_id', $language_id);
                },
            ])
            ->where('is_active', true)
            ->find($category_id);
    }

    /**
     * Rebuild category paths for this category
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
     */
    public function getLevel(): int
    {
        return $this->categoryPaths()
            ->where('category_id', $this->id)
            ->max('level') ?? 0;
    }

    /**
     * @return Collection<Category>
     */
    public function getCategoryByParentId(int $parent_id): Collection
    {
        return self::query()
            ->where('parent_id', $parent_id)
            ->get();
    }

    public function getActiveCategoriesWithDescriptionsByLanguageIdAndPathIds(int $language_id, array $path_ids): Collection
    {
        return self::query()
            ->with([
                'categoryDescription' => function ($query) use ($language_id) {
                    $query->where('language_id', $language_id);
                },
            ])
            ->whereIn('id', $path_ids)
            ->get();
    }

    /**
     * @return Collection<Category>
     */
    public function getActiveCategoriesWithDescriptionsAndPathByLanguageId(int $language_id): Collection
    {
        return self::query()
            ->with([
                'categoryDescription' => function ($query) use ($language_id) {
                    $query->where('language_id', $language_id);
                },
                'categoryPaths' => function ($query) {
                    $query->orderBy('level', 'desc');
                },
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
