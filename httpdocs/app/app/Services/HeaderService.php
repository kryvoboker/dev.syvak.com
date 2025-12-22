<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalogs\Categories\Category;
use App\Models\Settings\Language;
use Illuminate\Database\Eloquent\Collection;

class HeaderService
{
    /**
     * @param array $params
     *
     * @return array
     */
    public function __invoke(array $params = []): array
    {
        $category     = new Category();
        $app_settings = get_app_settings();
        $categories   = $category->getActiveCategoriesWithDescriptionsByLanguageId(
            $app_settings->language_id
        );
        $languages    = new Language()->getActiveLanguages();

        return [
            'logo_urls'                => multiple_convert_img_and_get_url(
                config('app.images.path_to_logo'),
                (int)($app_settings->image_sizes['logo']['width'] ?? config('app.images.logo_width')),
                (int)($app_settings->image_sizes['logo']['height'] ?? config('app.images.logo_height')),
                is_square: false
            ),
            'breadcrumbs'              => $params['breadcrumbs'] ?? [],
            'categories'               => $categories,
            'hoodie_category'          => $categories->firstWhere('id', (int)config('app.categories.hoodie_id')),
            'exclusive_gifts_category' => $categories->firstWhere('id', (int)config('app.categories.exclusive_gifts_id')),
            'languages'                => $languages,
            'menu_data'                => $this->processCreateMainMenu($categories, $languages),
        ];
    }

    /**
     * @param Collection<Category> $categories
     * @param Collection<Language> $languages
     *
     * @return array
     */
    private function processCreateMainMenu(Collection $categories, Collection $languages): array
    {
        return [
            'categories'       => $categories,
            'languages'        => $languages,
            'socials'          => get_app_settings()->socials,
            'current_language' => app()->getLocale(),
        ];
    }
}
