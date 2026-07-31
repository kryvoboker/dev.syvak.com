<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Categories\Category;
use App\Services\PageSettings\HeaderCategoryService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HeaderService
{
    public function __invoke(array $params = []): array
    {
        $category = new Category();
        $app_settings = get_app_settings();
        $logo_sizes = $app_settings->image_sizes?->firstWhere('name', 'logo') ?? [];
        $logo_path = (string)data_get(
            $app_settings,
            'system_settings.images.path_to_logo',
            (string)config('app.images.path_to_logo', 'images/logo.png'),
        );
        $categories = $category->getActiveCategoriesWithDescriptionsAndSlugsByLanguageId(
            $app_settings->language_id,
        );
        $categories->load('categoryImage');

        $current_device_type = config('devices.current_device_type');
        $is_desktop_device = (string)($current_device_type ?? config('devices.types.desktop'))
            === (string)config('devices.types.desktop');

        $categories = $categories->map(function ($category_item) use ($is_desktop_device): array {
            /** @var Category $category */
            $category = $category_item;

            $category_data = [
                'id' => (int)$category->id,
                'descriptions' => $category->categoryDescription->first()->toArray(),
                'slug' => $category->slugs->first()->slug,
            ];

            if ($is_desktop_device) {
                $category_data['preview_image'] = $this->resolveCategoryPreviewImage($category);
            }

            return $category_data;
        });
        $header_categories = $this->resolveHeaderCategories((int)$app_settings->language_id);
        $languages = (new Language())->getActiveLanguages();
        $logo_width = (int)($logo_sizes['width'] ?? config('app.images.logo_width'));
        $logo_height = (int)($logo_sizes['height'] ?? config('app.images.logo_height'));
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
            'header_categories' => $header_categories,
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
     * @return array<int, array{id:int, descriptions:array<string, mixed>, slug:string}>
     */
    private function resolveHeaderCategories(int $language_id): array
    {
        try {
            $settings = app(PageSettingsBootstrapService::class)->getCategorySettings();
            $category_ids = app(HeaderCategoryService::class)->normalizeCategoryIds(
                Arr::get($settings, 'header.categories', []),
            );

            return app(HeaderCategoryService::class)
                ->getActiveCategories($category_ids, $language_id)
                ->map(function (Category $category): ?array {
                    $description = $category->categoryDescription->first();
                    $slug = $category->slugs->first()?->slug;

                    if ($description === null || blank($description->name) || blank($slug)) {
                        return null;
                    }

                    return [
                        'id' => (int)$category->id,
                        'descriptions' => $description->toArray(),
                        'slug' => (string)$slug,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        } catch (Throwable $throwable) {
            Log::channel('stack')->warning('Header categories payload resolution failed.', [
                'language_id' => $language_id,
                'exception' => $throwable,
            ]);

            return [];
        }
    }

    /**
     * @param Collection<Language> $languages
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

    /**
     * @return array{urls: array<string, string>, width: int, height: int, alt: string}
     */
    private function resolveCategoryPreviewImage(Category $category): array
    {
        $category_image = $category->categoryImage->first();
        $preview_image_path = (string)($category_image !== null ? $category_image->preview_image : '');
        $preview_image_width = $category_image?->preview_image_width;
        $preview_image_height = $category_image?->preview_image_height;
        $fallback_image_path = (string)(config('app.images.default_no_image') ?: 'images/no-image.png');

        if (blank($preview_image_path) || Storage::fileExists($preview_image_path) === false) {
            $preview_image_path = $fallback_image_path;
        }

        try {
            $preview_image_width ??= (int)config('app.images.category.preview_in_page_in_catalog_menu.width', 0);
            $preview_image_height ??= (int)config('app.images.category.preview_in_page_in_catalog_menu.height', 0);

            return [
                'urls' => multiple_convert_img_and_get_url(
                    $preview_image_path,
                    $preview_image_width,
                    $preview_image_height,
                    is_square: false,
                    bg_color : 'transparent',
                ),
                'width' => $preview_image_width,
                'height' => $preview_image_height,
                'alt' => (string)data_get($category->categoryDescription->first(), 'name', ''),
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->warning('Category preview image resolution failed.', [
                'category_id' => $category->id,
                'image_path' => $preview_image_path,
                'exception' => $throwable,
            ]);

            return [
                'urls' => multiple_convert_img_and_get_url(
                    $fallback_image_path,
                    $preview_image_width,
                    $preview_image_height,
                    is_square: false,
                    bg_color : 'transparent',
                ),
                'width' => $preview_image_width,
                'height' => $preview_image_height,
                'alt' => (string)data_get($category->categoryDescription->first(), 'name', ''),
            ];
        }
    }
}
