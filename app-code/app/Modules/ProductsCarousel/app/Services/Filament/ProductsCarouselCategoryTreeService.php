<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Services\Filament;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Categories\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Builds category options for Filament checkbox lists in a deterministic tree order.
 */
class ProductsCarouselCategoryTreeService
{
    /**
     * @return array<int, string>
     */
    public function getCheckboxTreeOptions(): array
    {
        $started_at = microtime(true);

        $active_categories = $this->getActiveCategories();

        /** @var array<int, Collection<int, Category>> $categories_by_parent */
        $categories_by_parent = $active_categories
            ->sortBy(['sort_order', 'id'])
            ->groupBy(fn (Category $category): int => (int) ($category->parent_id ?? 0))
            ->all();

        $options = [];

        $build_options = function (int $parent_id, int $depth) use (&$build_options, &$options, $categories_by_parent): void {
            $categories = $categories_by_parent[$parent_id] ?? collect();

            foreach ($categories as $category) {
                $options[$category->id] = str_repeat('— ', $depth) . $this->resolveCategoryLabel($category);
                $build_options((int) $category->id, $depth + 1);
            }
        };

        $build_options(0, 0);

        Log::channel('daily')->info('ProductsCarousel category tree options generated.', [
            'categories_count' => count($options),
            'elapsed_ms' => (int) ((microtime(true) - $started_at) * 1000),
        ]);

        return $options;
    }

    /**
     * @return array<int>
     */
    public function getAllActiveCategoryIds(): array
    {
        return array_keys($this->getCheckboxTreeOptions());
    }

    /**
     * @return Collection<int, Category>
     */
    private function getActiveCategories(): Collection
    {
        $language_id = $this->resolveLanguageId();

        return Category::query()
            ->select(['id', 'parent_id', 'sort_order'])
            ->where('is_active', true)
            ->with([
                'categoryDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function resolveCategoryLabel(Category $category): string
    {
        $description = $category->categoryDescription->first();

        return is_string($description?->name) && filled($description->name)
            ? $description->name
            : 'Category #' . $category->id;
    }

    private function resolveLanguageId(): int
    {
        $locale = app()->getLocale();

        $language_by_locale = Language::query()
            ->where('is_active', true)
            ->where('code', $locale)
            ->first();

        if ($language_by_locale !== null) {
            return (int) $language_by_locale->id;
        }

        $default_language = (new Language())->getDefaultLanguage();

        if ($default_language !== null) {
            return (int) $default_language->id;
        }

        return (int) Language::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');
    }
}
