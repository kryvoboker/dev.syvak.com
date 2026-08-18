<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Categories\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class HeaderCategoryService
{
    /**
     * @return array<int, int>
     */
    public function getSelectedCategoryIds(): array
    {
        try {
            $settings = app(PageSettingsBootstrapService::class)->getCategorySettings();

            return $this->normalizeActiveCategoryIds(Arr::get($settings, 'header.categories', []));
        } catch (Throwable $throwable) {
            Log::channel('stack')->warning('Header category selection loading failed.', [
                'exception' => $throwable,
            ]);

            return [];
        }
    }

    public function setCategoryVisibility(int $category_id, bool $is_visible): void
    {
        if ($category_id <= 0) {
            return;
        }

        try {
            $page_setting = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();
            $settings = is_array($page_setting->settings) ? $page_setting->settings : [];
            $category_ids = $this->normalizeActiveCategoryIds(Arr::get($settings, 'header.categories', []));
            $is_active_category = $this->normalizeActiveCategoryIds([$category_id]) === [$category_id];

            if ($is_visible && $is_active_category && ! in_array($category_id, $category_ids, true)) {
                $category_ids[] = $category_id;
            }

            if (! $is_visible || ! $is_active_category) {
                $category_ids = array_values(array_filter(
                    $category_ids,
                    fn (int $selected_category_id): bool => $selected_category_id !== $category_id,
                ));
            }

            Arr::set($settings, 'header.categories', $category_ids);

            $page_setting->forceFill([
                'settings' => $settings,
            ])->save();
        } catch (Throwable $throwable) {
            Log::channel('stack')->warning('Header category visibility update failed.', [
                'category_id' => $category_id,
                'is_visible' => $is_visible,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @param  array<int|string, mixed>  $selected_category_ids
     * @return array<int, string>
     */
    public function searchOptions(string $search, array $selected_category_ids = []): array
    {
        $search = Str::trim($search);

        if (blank($search)) {
            return [];
        }

        try {
            $active_language_ids = (new Language())->getActiveLanguages()->modelKeys();

            if ($active_language_ids === []) {
                return [];
            }

            $excluded_ids = $this->normalizeCategoryIds($selected_category_ids);
            $categories = Category::query()
                ->with([
                    'categoryDescription' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($active_language_ids): void {
                        $query->whereIn('language_id', $active_language_ids);
                    },
                ])
                ->where('is_active', true)
                ->whereNotIn('id', $excluded_ids)
                ->whereHas(
                    'categoryDescription',
                    fn (Builder $query): Builder => $query
                        ->whereIn('language_id', $active_language_ids)
                        ->where('name', 'like', "%$search%"),
                )
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(50)
                ->get();

            return $categories
                ->mapWithKeys(fn (Category $category): array => [
                    (string) $category->id => $this->resolveCategoryLabel($category),
                ])
                ->all();
        } catch (Throwable $throwable) {
            Log::channel('stack')->warning('Header category search failed.', [
                'search' => $search,
                'selected_category_ids' => $selected_category_ids,
                'exception' => $throwable,
            ]);

            return [];
        }
    }

    public function getOptionLabel(int|string|null $category_id): ?string
    {
        $normalized_id = $this->normalizeCategoryIds([$category_id])[0] ?? null;

        if ($normalized_id === null) {
            return null;
        }

        try {
            $category = Category::query()
                ->with('categoryDescription')
                ->where('is_active', true)
                ->find($normalized_id);

            return $category instanceof Category
                ? $this->resolveCategoryLabel($category)
                : null;
        } catch (Throwable $throwable) {
            Log::channel('stack')->warning('Header category label resolution failed.', [
                'category_id' => $normalized_id,
                'exception' => $throwable,
            ]);

            return null;
        }
    }

    /**
     * @param  mixed  $category_ids
     * @return array<int, int>
     */
    public function normalizeCategoryIds(mixed $category_ids): array
    {
        if (! is_array($category_ids)) {
            return [];
        }

        return collect($category_ids)
            ->map(function (mixed $category_id): ?int {
                if (! is_int($category_id) && ! is_string($category_id) && ! is_numeric($category_id)) {
                    return null;
                }

                $normalized_id = (int) $category_id;

                return $normalized_id > 0 ? $normalized_id : null;
            })
            ->filter(fn (?int $category_id): bool => $category_id !== null)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $category_ids
     * @return array<int, int>
     */
    public function normalizeActiveCategoryIds(mixed $category_ids): array
    {
        $normalized_ids = $this->normalizeCategoryIds($category_ids);

        if ($normalized_ids === []) {
            return [];
        }

        try {
            $active_ids = Category::query()
                ->where('is_active', true)
                ->whereIn('id', $normalized_ids)
                ->pluck('id')
                ->map(fn (mixed $category_id): int => is_numeric($category_id) ? (int) $category_id : 0)
                ->all();

            return collect($normalized_ids)
                ->filter(fn (int $category_id): bool => in_array($category_id, $active_ids, true))
                ->values()
                ->all();
        } catch (Throwable $throwable) {
            Log::channel('stack')->warning('Active header category normalization failed.', [
                'category_ids' => $normalized_ids,
                'exception' => $throwable,
            ]);

            return [];
        }
    }

    /**
     * @param  array<int|string, mixed>  $category_ids
     * @return Collection<int, Category>
     */
    public function getActiveCategories(array $category_ids, int $language_id): Collection
    {
        $normalized_ids = $this->normalizeCategoryIds($category_ids);

        if ($normalized_ids === []) {
            return new Collection();
        }

        try {
            $categories = Category::query()
                ->with([
                    'categoryDescription' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($language_id): void {
                        $query->where('language_id', $language_id);
                    },
                    'slugs' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($language_id): void {
                        $query->where('language_id', $language_id);
                    },
                ])
                ->where('is_active', true)
                ->whereIn('id', $normalized_ids)
                ->get()
                ->keyBy('id');

            $ordered_categories = collect($normalized_ids)
                ->map(fn (int $category_id): ?Category => $categories->get($category_id))
                ->filter(fn (?Category $category): bool => $category instanceof Category)
                ->values()
                ->all();

            /** @var Collection<int, Category> $result */
            $result = new Collection($ordered_categories);

            return $result;
        } catch (Throwable $throwable) {
            Log::channel('stack')->warning('Header categories resolution failed.', [
                'category_ids' => $normalized_ids,
                'language_id' => $language_id,
                'exception' => $throwable,
            ]);

            return new Collection();
        }
    }

    private function resolveCategoryLabel(Category $category): string
    {
        $current_language_id = $this->resolveCurrentLanguageId();
        $description = $category->categoryDescription
            ->firstWhere('language_id', $current_language_id)
            ?? $category->categoryDescription->first();

        return (string) ($description?->name ?: "Category #$category->id");
    }

    private function resolveCurrentLanguageId(): ?int
    {
        $language = (new Language())->getLanguageByCode(app()->getLocale());

        if ($language !== null) {
            return (int) $language->id;
        }

        return (new Language())->getDefaultLanguage()?->id;
    }
}
