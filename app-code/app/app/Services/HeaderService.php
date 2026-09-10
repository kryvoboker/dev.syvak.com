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
    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function __invoke(array $params = []): array
    {
        $category = new Category();
        $app_settings = get_app_settings() ?? throw new \LogicException('Application settings are not initialized.');
        $logo_sizes = array_value($app_settings->image_sizes?->firstWhere('name', 'logo'));
        $logo_path = string_value(data_get(
            $app_settings,
            'system_settings.images.path_to_logo',
            string_value(config('app.images.path_to_logo', 'images/logo.png')),
        ));
        $categories = $category->getActiveCategoriesWithDescriptionsAndSlugsByLanguageId(
            $app_settings->language_id ?? 0,
        );
        $categories->load('categoryImage');

        $current_device_type = string_value(config('devices.current_device_type', config('devices.types.desktop')));
        $is_desktop_device = $current_device_type === string_value(config('devices.types.desktop'));

        $categories = $categories->toBase()->map(function (Category $category) use ($is_desktop_device): array {

            $category_data = [
                'id' => integer_value($category->id),
                'descriptions' => array_value($category->categoryDescription->first()?->toArray() ?? []),
                'slug' => string_value($category->slugs->first()?->slug),
            ];

            if ($is_desktop_device) {
                $category_data['preview_image'] = $this->resolveCategoryPreviewImage($category);
            }

            return $category_data;
        });
        $header_categories = $this->resolveHeaderCategories((int)$app_settings->language_id);
        $languages = (new Language())->getActiveLanguages();
        $logo_width = integer_value($logo_sizes['width'] ?? config('app.images.logo_width'));
        $logo_height = integer_value($logo_sizes['height'] ?? config('app.images.logo_height'));
        $socials = array_map(function (mixed $item): array {
            $item = array_value($item);
            if (isset($item['svg_icon'])) {
                $item['svg_icon'] = escape_special_html(string_value($item['svg_icon']));
            }

            return $item;
        }, array_value(Arr::get($app_settings->socials?->all() ?? [], app()->getLocale(), [])));

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

            $resolved_categories = app(HeaderCategoryService::class)
                ->getActiveCategories($category_ids, $language_id)
                ->toBase()
                ->map(function (Category $category): ?array {
                    $description = $category->categoryDescription->first();
                    $slug = $category->slugs->first()?->slug;

                    if ($description === null || blank($description->name) || blank($slug)) {
                        return null;
                    }

                    $description_data = $description->toArray();
                    /** @var array<string, mixed> $description_data */

                    return [
                        'id' => integer_value($category->id),
                        'descriptions' => $description_data,
                        'slug' => string_value($slug),
                    ];
                })
                ->filter(fn (?array $category): bool => $category !== null)
                ->values()
                ->all();

            /** @var array<int, array{id: int, descriptions: array<string, mixed>, slug: string}> $resolved_categories */
            return $resolved_categories;
        } catch (Throwable $throwable) {
            Log::channel('stack')->warning('Header categories payload resolution failed.', [
                'language_id' => $language_id,
                'exception' => $throwable,
            ]);

            return [];
        }
    }

    /**
     * @param \Illuminate\Support\Collection<int, array{id: int, descriptions: array<mixed>, slug: string, preview_image?: array{urls: array<string, string>, width: int, height: int, alt: string}}> $categories
     * @param Collection<int, Language> $languages
     * @return array<string, mixed>
     */
    private function processCreateMainMenu(\Illuminate\Support\Collection $categories, Collection $languages): array
    {
        return [
            'categories' => $categories,
            'languages' => $languages,
            'socials' => get_app_settings()?->socials,
            'current_language' => app()->getLocale(),
        ];
    }

    /**
     * @return array{urls: array<string, string>, width: int, height: int, alt: string}
     */
    private function resolveCategoryPreviewImage(Category $category): array
    {
        $category_image = $category->categoryImage->first();
        $preview_image_path = string_value($category_image?->preview_image);
        $preview_image_width = integer_value(data_get(
            $category_image,
            'preview_image_width',
            config('app.images.category.preview_in_page_in_catalog_menu.width', 0),
        ));
        $preview_image_height = integer_value(data_get(
            $category_image,
            'preview_image_height',
            config('app.images.category.preview_in_page_in_catalog_menu.height', 0),
        ));
        $fallback_image_path = string_value(config('app.images.default_no_image') ?: 'images/no-image.png');

        if (blank($preview_image_path) || Storage::fileExists($preview_image_path) === false) {
            $preview_image_path = $fallback_image_path;
        }

        try {
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
                'alt' => string_value(data_get($category->categoryDescription->first(), 'name', '')),
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
                'alt' => string_value(data_get($category->categoryDescription->first(), 'name', '')),
            ];
        }
    }
}
