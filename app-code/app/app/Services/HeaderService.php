<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Categories\Category;
use Illuminate\Database\Eloquent\Collection;

class HeaderService
{
    public function __invoke(array $params = []): array
    {
        $category = new Category();
        $app_settings = get_app_settings();
        $logo_sizes = $app_settings->image_sizes?->firstWhere('name', 'logo') ?? [];
        $logo_path = (string) data_get(
            $app_settings,
            'system_settings.images.path_to_logo',
            (string) config('app.images.path_to_logo', 'images/logo.png'),
        );
        $categories = $category->getActiveCategoriesWithDescriptionsAndSlugsByLanguageId(
            $app_settings->language_id,
        )
            ->map(function ($category_item): array {
                /** @var Category $category */
                $category = $category_item;

                return [
                    'id' => (int) $category->id,
                    'descriptions' => $category->categoryDescription->first()->toArray(),
                    'slug' => $category->slugs->first()->slug,
                ];
            });
        $languages = (new Language())->getActiveLanguages();
        $logo_width = (int) ($logo_sizes['width'] ?? config('app.images.logo_width'));
        $logo_height = (int) ($logo_sizes['height'] ?? config('app.images.logo_height'));
        $socials = array_map(function ($item) {
            if (isset($item['svg_icon'])) {
                $item['svg_icon'] = escape_special_html($item['svg_icon']);
            }

            return $item;
        }, $app_settings->socials[app()->getLocale()] ?? []);

        return [
            'logo_data' => [
                'urls' => multiple_convert_img_and_get_url(
                    $logo_path,
                    $logo_width,
                    $logo_height,
                    is_square: false,
                ),
                'width' => $logo_width,
                'height' => $logo_height,
            ],
            'breadcrumbs' => $params['breadcrumbs'] ?? [],
            'categories' => $categories,
            'hoodie_category' => $categories->firstWhere('id', (int) config('app.categories.hoodie_id')),
            'exclusive_gifts_category' => $categories->firstWhere('id', (int) config('app.categories.exclusive_gifts_id')),
            'languages' => $languages,
            'menu_data' => $this->processCreateMainMenu($categories, $languages),
            'socials' => $socials,
            'sluggable_type' => $params['sluggable_type'] ?? null,
            'slug' => $params['slug'] ?? null,
            'variant_slug' => $params['variant_slug'] ?? null,
            'attribute_filters' => is_array($params['attribute_filters'] ?? null) ? $params['attribute_filters'] : [],
        ];
    }

    /**
     * @param  Collection<Language>  $languages
     */
    private function processCreateMainMenu(\Illuminate\Support\Collection $categories, Collection $languages): array
    {
        return [
            'categories' => $categories,
            'languages' => $languages,
            'socials' => get_app_settings()->socials,
            'current_language' => app()->getLocale(),
        ];
    }
}
