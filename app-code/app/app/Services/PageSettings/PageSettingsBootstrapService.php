<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Models\ApplicationSettings\AppSetting;
use App\Models\ApplicationSettings\Language;
use App\Models\PageSettings\PageSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PageSettingsBootstrapService
{
    /**
     * @throws Throwable
     *
     * @return array<string, mixed>
     */
    public function getCategorySettings(): array
    {
        $page_setting = $this->bootstrapCategoryPageSetting();

        return is_array($page_setting->settings) ? $page_setting->settings : [];
    }

    /**
     * @throws Throwable
     *
     * @return array{width:int,height:int}
     */
    public function getCategoryProductImageSize(): array
    {
        $settings = $this->getCategorySettings();

        return [
            'width' => max(1, $this->integerValue(Arr::get($settings, 'images.products.width', $this->integerValue(config('app.page_settings.category.product_image_width', 420))))),
            'height' => max(1, $this->integerValue(Arr::get($settings, 'images.products.height', $this->integerValue(config('app.page_settings.category.product_image_height', 420))))),
        ];
    }

    /**
     * @throws Throwable
     *
     * @return array<string, mixed>
     */
    public function getCategoryAdminImageSettings(): array
    {
        $settings = $this->getCategorySettings();

        return [
            'upload' => [
                'max_size_kb' => max(
                    1,
                    $this->integerValue(Arr::get($settings, 'admin.upload.max_size_kb', $this->integerValue(config('app.images.category.upload.max_size_kb', 5120)))),
                ),
                'directory' => resolve_upload_path_placeholders($this->stringValue(Arr::get(
                    $settings,
                    'admin.upload.directory',
                    normalize_upload_path_template($this->stringValue(config('app.images.category.image_path', 'images/categories/' . date('Y/m')))),
                ))),
            ],
            'images' => [
                'no_image' => [
                    'path' => $this->stringValue(Arr::get(
                        $settings,
                        'admin.images.no_image.path',
                        $this->stringValue(config('app.images.category.no_image', 'images/no-image.png')),
                    )),
                ],
                'preview_in_list' => [
                    'width' => max(
                        1,
                        $this->integerValue(Arr::get(
                            $settings,
                            'admin.images.preview_in_list.width',
                            $this->integerValue(config('app.images.category.preview_in_list_in_admin.width', 100)),
                        )),
                    ),
                    'height' => max(
                        1,
                        $this->integerValue(Arr::get(
                            $settings,
                            'admin.images.preview_in_list.height',
                            $this->integerValue(config('app.images.category.preview_in_list_in_admin.height', 100)),
                        )),
                    ),
                ],
                'preview_in_page' => [
                    'width' => max(
                        1,
                        $this->integerValue(Arr::get(
                            $settings,
                            'admin.images.preview_in_page.width',
                            $this->integerValue(config('app.images.category.preview_in_page_in_admin.width', 500)),
                        )),
                    ),
                    'height' => max(
                        1,
                        $this->integerValue(Arr::get(
                            $settings,
                            'admin.images.preview_in_page.height',
                            $this->integerValue(config('app.images.category.preview_in_page_in_admin.height', 500)),
                        )),
                    ),
                ],
            ],
        ];
    }

    /**
     * @throws Throwable
     *
     * @return array<string, mixed>
     */
    public function getProductSettings(): array
    {
        $page_setting = $this->bootstrapProductPageSetting();

        return is_array($page_setting->settings) ? $page_setting->settings : [];
    }

    /**
     * @throws Throwable
     */
    public function getProductMinimumStockQuantity(): int
    {
        $settings = $this->getProductSettings();

        return max(
            0,
            $this->integerValue(Arr::get(
                $settings,
                'customer.stock.minimum_stock_quantity',
                $this->integerValue(Arr::get(
                    $settings,
                    'stock.minimum_stock_quantity',
                    $this->integerValue(config('app.page_settings.product.for_customer.minimum_stock_quantity', $this->integerValue(config('app.page_settings.product.minimum_stock_quantity', 1)))),
                )),
            )),
        );
    }

    /**
     * @throws Throwable
     */
    public function getProductEanMaxLength(): int
    {
        $settings = $this->getProductSettings();

        return max(
            1,
            $this->integerValue(Arr::get(
                $settings,
                'admin.validation.ean_max_length',
                $this->integerValue(Arr::get(
                    $settings,
                    'validation.ean_max_length',
                    $this->integerValue(config('app.page_settings.product.for_admin.ean_max_length', $this->integerValue(config('app.page_settings.product.ean_max_length', 13)))),
                )),
            )),
        );
    }

    /**
     * @throws Throwable
     */
    public function getProductUploadMaxSizeKb(): int
    {
        $settings = $this->getProductSettings();

        return max(
            1,
            $this->integerValue(Arr::get(
                $settings,
                'admin.upload.max_size_kb',
                $this->integerValue(config('app.page_settings.product.for_admin.upload_max_size_kb', $this->integerValue(config('app.images.product.upload.max_size_kb', 5120)))),
            )),
        );
    }

    /**
     * @throws Throwable
     */
    public function getProductImageUploadDirectory(): string
    {
        $settings = $this->getProductSettings();

        return resolve_upload_path_placeholders($this->stringValue(Arr::get(
            $settings,
            'admin.upload.directory',
            normalize_upload_path_template($this->stringValue(config('app.page_settings.product.for_admin.image_upload_directory', $this->stringValue(config('app.images.product.image_path', 'images/products/' . date('Y/m')))))),
        )));
    }

    /**
     * @throws Throwable
     */
    public function getProductNoImagePath(): string
    {
        $settings = $this->getProductSettings();

        return $this->stringValue(Arr::get(
            $settings,
            'admin.images.no_image.path',
            $this->stringValue(config('app.page_settings.product.for_admin.no_image', $this->stringValue(config('app.images.product.no_image', 'images/no-image.png')))),
        ));
    }

    /**
     * @throws Throwable
     *
     * @return array{width:int,height:int}
     */
    public function getProductPreviewInListSize(): array
    {
        $settings = $this->getProductSettings();

        return [
            'width' => max(
                1,
                $this->integerValue(Arr::get(
                    $settings,
                    'admin.images.preview_in_list.width',
                    $this->integerValue(config('app.page_settings.product.for_admin.preview_in_list_width', $this->integerValue(config('app.images.product.preview_in_list_in_admin.width', 100)))),
                )),
            ),
            'height' => max(
                1,
                $this->integerValue(Arr::get(
                    $settings,
                    'admin.images.preview_in_list.height',
                    $this->integerValue(config('app.page_settings.product.for_admin.preview_in_list_height', $this->integerValue(config('app.images.product.preview_in_list_in_admin.height', 100)))),
                )),
            ),
        ];
    }

    /**
     * @throws Throwable
     *
     * @return array{width:int,height:int}
     */
    public function getProductPreviewInPageSize(): array
    {
        $settings = $this->getProductSettings();

        return [
            'width' => max(
                1,
                $this->integerValue(Arr::get(
                    $settings,
                    'admin.images.preview_in_page.width',
                    $this->integerValue(config('app.page_settings.product.for_admin.preview_in_page_width', $this->integerValue(config('app.images.product.preview_in_page_in_admin.width', 500)))),
                )),
            ),
            'height' => max(
                1,
                $this->integerValue(Arr::get(
                    $settings,
                    'admin.images.preview_in_page.height',
                    $this->integerValue(config('app.page_settings.product.for_admin.preview_in_page_height', $this->integerValue(config('app.images.product.preview_in_page_in_admin.height', 500)))),
                )),
            ),
        ];
    }

    /**
     * @throws Throwable
     *
     * @return array<string, mixed>
     */
    public function getSearchSettings(): array
    {
        $page_setting = $this->bootstrapSearchPageSetting();

        return is_array($page_setting->settings) ? $page_setting->settings : [];
    }

    /**
     * @throws Throwable
     */
    public function getSearchProductsPerPageLimit(): int
    {
        $settings = $this->getSearchSettings();

        return max(
            1,
            $this->integerValue(Arr::get($settings, 'pagination.products_per_page_limit', $this->integerValue(config('app.page_settings.search.products_per_page_limit', 15)))),
        );
    }

    /**
     * @throws Throwable
     *
     * @return array{width:int,height:int}
     */
    public function getSearchProductImageSize(): array
    {
        $settings = $this->getSearchSettings();

        return [
            'width' => max(1, $this->integerValue(Arr::get($settings, 'images.search_product.width', $this->integerValue(config('app.page_settings.search.images.search_product.width', 219))))),
            'height' => max(1, $this->integerValue(Arr::get($settings, 'images.search_product.height', $this->integerValue(config('app.page_settings.search.images.search_product.height', 219))))),
        ];
    }

    /**
     * @throws Throwable
     *
     * @return array{path:string,width:int,height:int}
     */
    public function getSearchNotFoundImageData(): array
    {
        $settings = $this->getSearchSettings();

        return [
            'path' => $this->stringValue(Arr::get(
                $settings,
                'images.search_not_found.path',
                $this->stringValue(Arr::get($this->stringKeyedArray(config('app.page_settings.search', [])), 'images.search_not_found.path', $this->stringValue(config('app.images.default_image_search_not_found')))),
            )),
            'width' => max(1, $this->integerValue(Arr::get($settings, 'images.search_not_found.width', $this->integerValue(config('app.page_settings.search.images.search_not_found.width', 600))))),
            'height' => max(1, $this->integerValue(Arr::get($settings, 'images.search_not_found.height', $this->integerValue(config('app.page_settings.search.images.search_not_found.height', 600))))),
        ];
    }

    /**
     * @throws Throwable
     */
    public function bootstrapCategoryPageSetting(): PageSetting
    {
        try {
            $defaults = $this->resolveCategoryDefaults();

            $page_setting = PageSetting::query()->firstOrCreate(
                [
                    'page_type' => PageSetting::PAGE_TYPE_CATEGORY,
                ],
                [
                    'settings' => $this->buildCategorySettingsContract(
                        is_sorting_enabled              : true,
                        is_filtering_enabled            : false,
                        products_per_page_limit         : $defaults['products_per_page_limit'],
                        is_ajax_products_loading_enabled: $defaults['ajax_products_loading_enabled'],
                        product_image_width             : $defaults['product_image_width'],
                        product_image_height            : $defaults['product_image_height'],
                        category_upload_max_size_kb     : $defaults['category_upload_max_size_kb'],
                        category_upload_directory       : $defaults['category_upload_directory'],
                        category_no_image_path          : $defaults['category_no_image_path'],
                        category_preview_list_width     : $defaults['category_preview_list_width'],
                        category_preview_list_height    : $defaults['category_preview_list_height'],
                        category_preview_page_width     : $defaults['category_preview_page_width'],
                        category_preview_page_height    : $defaults['category_preview_page_height'],
                    ),
                ],
            );

            $this->syncCategorySettingsContract($page_setting, $defaults);
            $this->syncDefaultSortingItems($page_setting);
            $this->syncMissingTranslations($page_setting);
            $this->syncFilterItemsNode($page_setting);

            return $page_setting->fresh() ?? $page_setting;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Category page setting bootstrap failed.', [
                'page_type' => PageSetting::PAGE_TYPE_CATEGORY,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @throws Throwable
     */
    public function bootstrapProductPageSetting(): PageSetting
    {
        try {
            $defaults = $this->resolveProductDefaults();

            $page_setting = PageSetting::query()->firstOrCreate(
                [
                    'page_type' => PageSetting::PAGE_TYPE_PRODUCT,
                ],
                [
                    'settings' => $this->buildProductSettingsContract($defaults),
                ],
            );

            $this->syncProductSettingsContract($page_setting, $defaults);

            return $page_setting->fresh() ?? $page_setting;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Product page setting bootstrap failed.', [
                'page_type' => PageSetting::PAGE_TYPE_PRODUCT,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @throws Throwable
     */
    public function bootstrapSearchPageSetting(): PageSetting
    {
        try {
            $defaults = $this->resolveSearchDefaults();

            $page_setting = PageSetting::query()->firstOrCreate(
                [
                    'page_type' => PageSetting::PAGE_TYPE_SEARCH,
                ],
                [
                    'settings' => $this->buildSearchSettingsContract($defaults),
                ],
            );

            $this->syncSearchSettingsContract($page_setting, $defaults);

            return $page_setting->fresh() ?? $page_setting;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Search page setting bootstrap failed.', [
                'page_type' => PageSetting::PAGE_TYPE_SEARCH,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @throws Throwable
     *
     * @return array<string, mixed>
     */
    public function getNotFoundSettings(): array
    {
        $page_setting = $this->bootstrapNotFoundPageSetting();

        return is_array($page_setting->settings) ? $page_setting->settings : [];
    }

    /**
     * @throws Throwable
     */
    public function bootstrapNotFoundPageSetting(): PageSetting
    {
        try {
            $defaults = $this->resolveNotFoundDefaults();

            $page_setting = PageSetting::query()->firstOrCreate(
                [
                    'page_type' => PageSetting::PAGE_TYPE_NOT_FOUND,
                ],
                [
                    'settings' => $this->buildNotFoundSettingsContract($defaults),
                ],
            );

            $this->syncNotFoundSettingsContract($page_setting, $defaults);

            return $page_setting->fresh() ?? $page_setting;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Not found page setting bootstrap failed.', [
                'page_type' => PageSetting::PAGE_TYPE_NOT_FOUND,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @throws Throwable
     *
     * @return array<string, mixed>
     */
    public function getContactsSettings(): array
    {
        $page_setting = $this->bootstrapContactsPageSetting();

        return is_array($page_setting->settings) ? $page_setting->settings : [];
    }

    /**
     * @throws Throwable
     */
    public function bootstrapContactsPageSetting(): PageSetting
    {
        try {
            $defaults = $this->resolveContactsDefaults();

            $page_setting = PageSetting::query()->firstOrCreate(
                [
                    'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
                ],
                [
                    'settings' => $this->buildContactsSettingsContract($defaults),
                ],
            );

            $this->syncContactsSettingsContract($page_setting, $defaults);

            return $page_setting->fresh() ?? $page_setting;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Contacts page setting bootstrap failed.', [
                'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @throws Throwable
     */
    /** @return array<string, mixed> */
    public function getFailureSettings(): array
    {
        $page_setting = $this->bootstrapFailurePageSetting();

        return is_array($page_setting->settings) ? $page_setting->settings : [];
    }

    /**
     * @throws Throwable
     */
    public function bootstrapFailurePageSetting(): PageSetting
    {
        try {
            $defaults = $this->resolveFailureDefaults();

            $page_setting = PageSetting::query()->firstOrCreate(
                [
                    'page_type' => PageSetting::PAGE_TYPE_FAILURE,
                ],
                [
                    'settings' => $this->buildFailureSettingsContract($defaults),
                ],
            );

            $this->syncFailureSettingsContract($page_setting, $defaults);

            return $page_setting->fresh() ?? $page_setting;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Failure page setting bootstrap failed.', [
                'page_type' => PageSetting::PAGE_TYPE_FAILURE,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function normalizeFailureSettings(array $settings): array
    {
        return $this->normalizeFailureSettingsContract($settings, $this->resolveFailureDefaults());
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function normalizeContactsSettings(array $settings): array
    {
        return $this->normalizeContactsSettingsContract($settings, $this->resolveContactsDefaults());
    }

    /**
     * @return array{
     *     products_per_page_limit:int,
     *     ajax_products_loading_enabled:bool,
     *     product_image_width:int,
     *     product_image_height:int,
     *     category_upload_max_size_kb:int,
     *     category_upload_directory:string,
     *     category_no_image_path:string,
     *     category_preview_list_width:int,
     *     category_preview_list_height:int,
     *     category_preview_page_width:int,
     *     category_preview_page_height:int
     * }
     */
    private function resolveCategoryDefaults(): array
    {
        return [
            'products_per_page_limit' => $this->integerValue(config('app.page_settings.category.products_per_page_limit', 20)),
            'ajax_products_loading_enabled' => (bool) config('app.page_settings.category.ajax_products_loading_enabled', true),
            'product_image_width' => $this->integerValue(config('app.page_settings.category.product_image_width', 420)),
            'product_image_height' => $this->integerValue(config('app.page_settings.category.product_image_height', 420)),
            'category_upload_max_size_kb' => max(1, $this->integerValue(config('app.images.category.upload.max_size_kb', 5120))),
            'category_upload_directory' => normalize_upload_path_template($this->stringValue(config('app.images.category.image_path', 'images/categories/' . date('Y/m')))),
            'category_no_image_path' => $this->stringValue(config('app.images.category.no_image', 'images/no-image.png')),
            'category_preview_list_width' => max(1, $this->integerValue(config('app.images.category.preview_in_list_in_admin.width', 100))),
            'category_preview_list_height' => max(1, $this->integerValue(config('app.images.category.preview_in_list_in_admin.height', 100))),
            'category_preview_page_width' => max(1, $this->integerValue(config('app.images.category.preview_in_page_in_admin.width', 500))),
            'category_preview_page_height' => max(1, $this->integerValue(config('app.images.category.preview_in_page_in_admin.height', 500))),
        ];
    }

    /**
     * @return array{
     *     customer_minimum_stock_quantity:int,
     *     customer_image_width:int,
     *     customer_image_height:int,
     *     admin_ean_max_length:int,
     *     admin_upload_max_size_kb:int,
     *     admin_image_upload_directory:string,
     *     admin_no_image_path:string,
     *     admin_preview_in_list_width:int,
     *     admin_preview_in_list_height:int,
     *     admin_preview_in_page_width:int,
     *     admin_preview_in_page_height:int
     * }
     */
    private function resolveProductDefaults(): array
    {
        return [
            'customer_minimum_stock_quantity' => max(
                0,
                $this->integerValue(config('app.page_settings.product.for_customer.minimum_stock_quantity', $this->integerValue(config('app.page_settings.product.minimum_stock_quantity', 1)))),
            ),
            'customer_image_width' => max(
                1,
                $this->integerValue(config('app.page_settings.product.for_customer.image_width', $this->integerValue(config('app.page_settings.product.image_width', 500)))),
            ),
            'customer_image_height' => max(
                1,
                $this->integerValue(config('app.page_settings.product.for_customer.image_height', $this->integerValue(config('app.page_settings.product.image_height', 500)))),
            ),
            'admin_ean_max_length' => max(
                1,
                $this->integerValue(config('app.page_settings.product.for_admin.ean_max_length', $this->integerValue(config('app.page_settings.product.ean_max_length', 13)))),
            ),
            'admin_upload_max_size_kb' => max(
                1,
                $this->integerValue(config('app.page_settings.product.for_admin.upload_max_size_kb', $this->integerValue(config('app.images.product.upload.max_size_kb', 5120)))),
            ),
            'admin_image_upload_directory' => $this->stringValue(config(
                'app.page_settings.product.for_admin.image_upload_directory',
                normalize_upload_path_template($this->stringValue(config('app.images.product.image_path', 'images/products/' . date('Y/m')))),
            )),
            'admin_no_image_path' => $this->stringValue(config(
                'app.page_settings.product.for_admin.no_image',
                $this->stringValue(config('app.images.product.no_image', 'images/no-image.png')),
            )),
            'admin_preview_in_list_width' => max(
                1,
                $this->integerValue(config('app.page_settings.product.for_admin.preview_in_list_width', $this->integerValue(config('app.images.product.preview_in_list_in_admin.width', 100)))),
            ),
            'admin_preview_in_list_height' => max(
                1,
                $this->integerValue(config('app.page_settings.product.for_admin.preview_in_list_height', $this->integerValue(config('app.images.product.preview_in_list_in_admin.height', 100)))),
            ),
            'admin_preview_in_page_width' => max(
                1,
                $this->integerValue(config('app.page_settings.product.for_admin.preview_in_page_width', $this->integerValue(config('app.images.product.preview_in_page_in_admin.width', 500)))),
            ),
            'admin_preview_in_page_height' => max(
                1,
                $this->integerValue(config('app.page_settings.product.for_admin.preview_in_page_height', $this->integerValue(config('app.images.product.preview_in_page_in_admin.height', 500)))),
            ),
        ];
    }

    /**
     * @return array{products_per_page_limit:int,search_product_width:int,search_product_height:int,search_not_found_path:string,search_not_found_width:int,search_not_found_height:int}
     */
    private function resolveSearchDefaults(): array
    {
        $legacy_image_sizes = $this->arrayValue(AppSetting::query()->value('image_sizes'));

        $legacy_search_product_size = collect($legacy_image_sizes)
            ->first(fn (mixed $item): bool => $this->stringValue(Arr::get($this->arrayValue($item), 'name')) === 'search_product');

        $legacy_search_not_found_size = collect($legacy_image_sizes)
            ->first(fn (mixed $item): bool => $this->stringValue(Arr::get($this->arrayValue($item), 'name')) === 'search_not_found');

        return [
            'products_per_page_limit' => max(1, $this->integerValue(config('app.page_settings.search.products_per_page_limit', 15))),
            'search_product_width' => max(
                1,
                $this->integerValue(Arr::get($this->arrayValue($legacy_search_product_size), 'width', $this->integerValue(config('app.page_settings.search.images.search_product.width', 219)))),
            ),
            'search_product_height' => max(
                1,
                $this->integerValue(Arr::get($this->arrayValue($legacy_search_product_size), 'height', $this->integerValue(config('app.page_settings.search.images.search_product.height', 219)))),
            ),
            'search_not_found_path' => $this->stringValue(Arr::get(
                $this->stringKeyedArray(config('app.page_settings.search', [])),
                'images.search_not_found.path',
                $this->stringValue(config('app.images.default_image_search_not_found', 'images/search/not-found.jpg')),
            )),
            'search_not_found_width' => max(
                1,
                $this->integerValue(Arr::get($this->arrayValue($legacy_search_not_found_size), 'width', $this->integerValue(config('app.page_settings.search.images.search_not_found.width', 600)))),
            ),
            'search_not_found_height' => max(
                1,
                $this->integerValue(Arr::get($this->arrayValue($legacy_search_not_found_size), 'height', $this->integerValue(config('app.page_settings.search.images.search_not_found.height', 600)))),
            ),
        ];
    }

    /**
     * @return array{localized:array<int|string, array<string, mixed>>,images:array<string, array<string, mixed>>}
     */
    private function resolveNotFoundDefaults(): array
    {
        /** @var array<string, array<string, mixed>> $localized */
        $localized = [];

        foreach ((new Language())->getActiveLanguages() as $language) {
            $language_id = (string) $language->id;
            $language_code = (string) $language->code;

            $localized[$language_id] = [
                'title' => (string) __(
                    'http-statuses.404',
                    [],
                    $language_code,
                ),
                'description' => null,
                'link' => [
                    'label' => (string) __('admin/settings/not_found_page_settings.defaults.home_link_label', [], $language_code),
                    'url' => '/' . $language_code,
                ],
            ];
        }

        return [
            'localized' => $localized,
            'images' => [
                'slot_1' => [
                    'path' => '',
                    'width' => 600,
                    'height' => 600,
                    'is_square' => true,
                    'background' => 'transparent',
                    'custom_css_classes' => '',
                    'sort_order' => 10,
                ],
                'slot_2' => [
                    'path' => '',
                    'width' => 600,
                    'height' => 600,
                    'is_square' => true,
                    'background' => 'transparent',
                    'custom_css_classes' => '',
                    'sort_order' => 20,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFailureDefaults(): array
    {
        $localized = [];

        foreach ((new Language())->getActiveLanguages() as $language) {
            $language_id = (string) $language->id;
            $language_code = (string) $language->code;

            $localized[$language_id] = [
                'title' => (string) __('admin/settings/failure_page_settings.defaults.title', [], $language_code),
                'description' => (string) __('admin/settings/failure_page_settings.defaults.description', [], $language_code),
                'retry_button' => [
                    'label' => (string) __('admin/settings/failure_page_settings.defaults.retry_button', [], $language_code),
                ],
                'alternative_payment_button' => [
                    'label' => (string) __('admin/settings/failure_page_settings.defaults.alternative_payment_button', [], $language_code),
                ],
                'working_hours' => '',
            ];
        }

        return [
            'localized' => $localized,
            'images' => [],
            'buttons' => [
                'retry' => [
                    'enabled' => true,
                    'custom_css_classes' => '',
                ],
                'alternative_payment' => [
                    'enabled' => true,
                    'custom_css_classes' => '',
                ],
                'available_payment_methods' => [
                    'enabled' => true,
                ],
            ],
            'support_contacts' => [
                'use_contacts_working_hours' => true,
                'working_hours' => collect($localized)
                    ->map(fn (): array => ['content' => null])
                    ->all(),
                'use_contacts_phones' => true,
                'phones' => [],
                'use_contacts_emails' => true,
                'emails' => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    private function buildFailureSettingsContract(array $defaults): array
    {
        return [
            'meta' => [
                'contract_version' => 1,
            ],
            ...$defaults,
        ];
    }

    /**
     * @param array<string, mixed> $defaults
     */
    private function syncFailureSettingsContract(PageSetting $page_setting, array $defaults): void
    {
        $settings = is_array($page_setting->settings) ? $page_setting->settings : [];

        $page_setting->forceFill([
            'settings' => $this->normalizeFailureSettingsContract($settings, $defaults),
        ])->save();
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    private function normalizeFailureSettingsContract(array $settings, array $defaults): array
    {
        $normalized_localized = [];

        foreach ((array) Arr::get($defaults, 'localized', []) as $language_id => $default_content) {
            $default_content = $this->stringKeyedArray($default_content);
            $content = Arr::get($settings, "localized.$language_id", []);
            $content = is_array($content) ? $content : [];

            $normalized_localized[$language_id] = [
                'title' => Str::trim($this->stringValue(Arr::get($content, 'title', Arr::get($default_content, 'title', '')))),
                'description' => filled(Arr::get($content, 'description'))
                    ? Str::trim($this->stringValue(Arr::get($content, 'description')))
                    : null,
                'retry_button' => [
                    'label' => Str::trim($this->stringValue(Arr::get($content, 'retry_button.label', Arr::get($default_content, 'retry_button.label', '')))),
                ],
                'alternative_payment_button' => [
                    'label' => Str::trim($this->stringValue(Arr::get($content, 'alternative_payment_button.label', Arr::get($default_content, 'alternative_payment_button.label', '')))),
                ],
                'working_hours' => filled(Arr::get($content, 'working_hours'))
                    ? Str::trim($this->stringValue(Arr::get($content, 'working_hours')))
                    : null,
            ];
        }

        $images = collect($this->arrayValue(Arr::get($settings, 'images', [])))
            ->filter(fn (mixed $image): bool => is_array($image))
            ->map(function (array $image): array {
                $background = $this->stringValue(Arr::get($image, 'background', 'transparent'));

                return [
                    'path' => Str::trim($this->stringValue(Arr::get($image, 'path', ''))),
                    'width' => max(1, $this->integerValue(Arr::get($image, 'width', 600))),
                    'height' => max(1, $this->integerValue(Arr::get($image, 'height', 600))),
                    'is_square' => (bool) Arr::get($image, 'is_square', true),
                    'background' => preg_match('/^(transparent|#[0-9a-fA-F]{6})$/', $background) === 1
                        ? $background
                        : 'transparent',
                    'custom_css_classes' => Str::squish($this->stringValue(Arr::get($image, 'custom_css_classes', ''))),
                    'sort_order' => max(0, $this->integerValue(Arr::get($image, 'sort_order', 0))),
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();

        $alternative_payment_enabled = (bool) Arr::get($settings, 'buttons.alternative_payment.enabled', true);
        $available_payment_methods_enabled = (bool) Arr::get($settings, 'buttons.available_payment_methods.enabled', true)
            || $alternative_payment_enabled;

        $normalized_settings = [
            'localized' => $normalized_localized,
            'images' => $images,
            'buttons' => [
                'available_payment_methods' => [
                    'enabled' => $available_payment_methods_enabled,
                ],
                'retry' => [
                    'enabled' => (bool) Arr::get($settings, 'buttons.retry.enabled', true),
                    'custom_css_classes' => Str::squish($this->stringValue(Arr::get($settings, 'buttons.retry.custom_css_classes', ''))),
                ],
                'alternative_payment' => [
                    'enabled' => $alternative_payment_enabled,
                    'custom_css_classes' => Str::squish($this->stringValue(Arr::get($settings, 'buttons.alternative_payment.custom_css_classes', ''))),
                ],
            ],
            'support_contacts' => [
                'use_contacts_working_hours' => (bool) Arr::get($settings, 'support_contacts.use_contacts_working_hours', true),
                'working_hours' => collect($this->arrayValue(Arr::get($settings, 'support_contacts.working_hours', [])))
                    ->filter(fn (mixed $content): bool => is_array($content))
                    ->map(fn (array $content): array => [
                        'content' => filled($content['content'] ?? null)
                            ? Str::trim($this->stringValue($content['content']))
                            : null,
                    ])
                    ->all(),
                'use_contacts_phones' => (bool) Arr::get($settings, 'support_contacts.use_contacts_phones', true),
                'phones' => $this->normalizeFailureContactItems(Arr::get($settings, 'support_contacts.phones', []), true),
                'use_contacts_emails' => (bool) Arr::get($settings, 'support_contacts.use_contacts_emails', true),
                'emails' => $this->normalizeFailureContactItems(Arr::get($settings, 'support_contacts.emails', [])),
            ],
        ];

        return array_replace_recursive(
            $this->buildFailureSettingsContract($defaults),
            $settings,
            [
                'meta' => ['contract_version' => 1],
                ...$normalized_settings,
            ],
        );
    }

    /**
     * @param mixed $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFailureContactItems(mixed $items, bool $is_phone = false): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item) use ($is_phone): array {
                $normalized = [
                    'value' => Str::trim($this->stringValue(Arr::get($item, 'value', ''))),
                    'sort_order' => max(0, $this->integerValue(Arr::get($item, 'sort_order', 0))),
                    'custom_css_classes' => Str::squish($this->stringValue(Arr::get($item, 'custom_css_classes', ''))),
                ];

                if ($is_phone) {
                    $normalized['type'] = in_array(Arr::get($item, 'type'), ['mobile', 'landline'], true)
                        ? Arr::get($item, 'type')
                        : 'mobile';
                }

                return $normalized;
            })
            ->filter(fn (array $item): bool => filled($item['value']))
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    /**
     * @param  array{localized:array<int|string, array<string, mixed>>,images:array<string, array<string, mixed>>}  $defaults
     * @return array<string, mixed>
     */
    private function buildNotFoundSettingsContract(array $defaults): array
    {
        return [
            'meta' => [
                'contract_version' => 1,
            ],
            'localized' => $defaults['localized'],
            'images' => $defaults['images'],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function normalizeNotFoundSettings(array $settings): array
    {
        return $this->normalizeNotFoundSettingsContract($settings, $this->resolveNotFoundDefaults());
    }

    /**
     * @param  array{localized:array<int|string, array<string, mixed>>,images:array<string, array<string, mixed>>}  $defaults
     */
    private function syncNotFoundSettingsContract(PageSetting $page_setting, array $defaults): void
    {
        $settings = is_array($page_setting->settings) ? $page_setting->settings : [];
        $page_setting->forceFill([
            'settings' => $this->normalizeNotFoundSettingsContract($settings, $defaults),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array{localized:array<int|string, array<string, mixed>>,images:array<string, array<string, mixed>>}  $defaults
     * @return array<string, mixed>
     */
    private function normalizeNotFoundSettingsContract(array $settings, array $defaults): array
    {
        $normalized_localized = [];

        foreach ($defaults['localized'] as $language_id => $default_content) {
            $default_content = $this->stringKeyedArray($default_content);
            $default_link = $this->stringKeyedArray(Arr::get($default_content, 'link', []));
            $content = Arr::get($settings, "localized.$language_id", []);
            $content = is_array($content) ? $content : [];

            $normalized_localized[$language_id] = [
                'title' => Str::trim($this->stringValue(Arr::get($content, 'title', Arr::get($default_content, 'title', '')))),
                'description' => filled(Arr::get($content, 'description'))
                    ? Str::trim($this->stringValue(Arr::get($content, 'description')))
                    : null,
                'link' => [
                    'label' => filled(Arr::get($content, 'link.label'))
                        ? Str::trim($this->stringValue(Arr::get($content, 'link.label')))
                        : null,
                    'url' => Str::trim($this->stringValue(Arr::get($content, 'link.url', Arr::get($default_link, 'url', '')))),
                ],
            ];
        }

        $normalized_images = [];

        foreach ($defaults['images'] as $slot => $default_image) {
            $image = Arr::get($settings, "images.$slot", []);
            $image = is_array($image) ? $image : [];
            $background = $this->stringValue(Arr::get($image, 'background', $default_image['background']));

            if ($background !== 'transparent' && preg_match('/^#[0-9a-fA-F]{6}$/', $background) !== 1) {
                $background = $default_image['background'];
            }

            $normalized_images[$slot] = [
                'path' => Str::trim($this->stringValue(Arr::get($image, 'path', $default_image['path']))),
                'width' => max(1, $this->integerValue(Arr::get($image, 'width', $default_image['width']))),
                'height' => max(1, $this->integerValue(Arr::get($image, 'height', $default_image['height']))),
                'is_square' => (bool) Arr::get($image, 'is_square', $default_image['is_square']),
                'background' => $background,
                'custom_css_classes' => Str::squish($this->stringValue(Arr::get($image, 'custom_css_classes', $default_image['custom_css_classes']))),
                'sort_order' => max(0, $this->integerValue(Arr::get($image, 'sort_order', $default_image['sort_order']))),
            ];
        }

        return array_replace_recursive(
            $this->buildNotFoundSettingsContract($defaults),
            $settings,
            [
                'meta' => [
                    'contract_version' => 1,
                ],
                'localized' => $normalized_localized,
                'images' => $normalized_images,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveContactsDefaults(): array
    {
        $localized = [];
        $email_templates = [];
        $telegram_templates = [];

        foreach ((new Language())->getActiveLanguages() as $language) {
            $language_id = (string) $language->id;
            $language_code = (string) $language->code;

            $localized[$language_id] = [
                'title' => $this->stringValue(__('admin/settings/contacts_page_settings.defaults.title', [], $language_code)),
                'working_hours' => [
                    'title' => $this->stringValue(__('admin/settings/contacts_page_settings.defaults.working_hours_title', [], $language_code)),
                    'description' => $this->stringValue(__('admin/settings/contacts_page_settings.defaults.working_hours_description', [], $language_code)),
                    'content' => $this->stringValue(__('admin/settings/contacts_page_settings.defaults.working_hours_content', [], $language_code)),
                ],
            ];

            $email_templates[$language_id] = [
                'subject' => $this->stringValue(__('admin/settings/contacts_page_settings.defaults.email_subject', [], $language_code)),
                'body' => $this->stringValue(__('admin/settings/contacts_page_settings.defaults.email_body', [], $language_code)),
            ];

            $telegram_templates[$language_id] = [
                'body' => $this->stringValue(__('admin/settings/contacts_page_settings.defaults.telegram_body', [], $language_code)),
            ];
        }

        $max_upload_size_kb = max(1, $this->integerValue(config('app.images.default_max_upload_image_size_kb', 5120)));

        return [
            'localized' => $localized,
            'images' => [],
            'phones' => [],
            'emails' => [],
            'addresses' => [],
            'contact_form' => [
                'fields' => [
                    'name' => ['enabled' => true, 'required' => true, 'regex' => null, 'min_length' => null, 'max_length' => 255],
                    'email' => ['enabled' => true, 'required' => true, 'regex' => null, 'min_length' => null, 'max_length' => 255],
                    'phone' => ['enabled' => true, 'required' => false, 'regex' => null, 'min_length' => null, 'max_length' => 50],
                    'text' => ['enabled' => true, 'required' => true, 'regex' => null, 'min_length' => null, 'max_length' => 5000],
                    'file' => [
                        'enabled' => true,
                        'required' => false,
                        'regex' => null,
                        'min_length' => null,
                        'max_length' => null,
                        'max_size_kb' => $max_upload_size_kb,
                        'allowed_types' => ['jpg', 'jpeg', 'png'],
                        'upload_path' => normalize_upload_path_template('images/contacts/{year}/{month}'),
                    ],
                ],
                'destinations' => [
                    'email' => ['enabled' => false, 'send_file' => false, 'address' => ''],
                    'telegram' => ['enabled' => false, 'send_file' => false, 'bot_token' => '', 'chat_id' => ''],
                ],
            ],
            'email' => ['templates' => $email_templates],
            'telegram' => ['templates' => $telegram_templates],
            'map' => [
                'iframe' => '',
                'latitude' => null,
                'longitude' => null,
                'width' => 600,
                'height' => 400,
                'custom_css_classes' => '',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function buildContactsSettingsContract(array $defaults): array
    {
        return [
            'meta' => ['contract_version' => 1],
            ...$defaults,
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     */
    private function syncContactsSettingsContract(PageSetting $page_setting, array $defaults): void
    {
        $settings = is_array($page_setting->settings) ? $page_setting->settings : [];

        $page_setting->forceFill([
            'settings' => $this->normalizeContactsSettingsContract($settings, $defaults),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function normalizeContactsSettingsContract(array $settings, array $defaults): array
    {
        $normalized_localized = [];

        foreach ((array) Arr::get($defaults, 'localized', []) as $language_id => $default_content) {
            $default_content = $this->stringKeyedArray($default_content);
            $default_working_hours = $this->stringKeyedArray(Arr::get($default_content, 'working_hours', []));
            $content = Arr::get($settings, "localized.$language_id", []);
            $content = is_array($content) ? $content : [];
            $working_hours = Arr::get($content, 'working_hours', []);
            $working_hours = is_array($working_hours) ? $working_hours : [];

            $normalized_localized[$language_id] = [
                'title' => Str::trim($this->stringValue(Arr::get($content, 'title', Arr::get($default_content, 'title', '')))),
                'working_hours' => [
                    'title' => Str::trim($this->stringValue(Arr::get($working_hours, 'title', Arr::get($default_working_hours, 'title', '')))),
                    'description' => $this->normalizeNullableString(Arr::get($working_hours, 'description', Arr::get($default_working_hours, 'description'))),
                    'content' => Str::trim($this->stringValue(Arr::get($working_hours, 'content', Arr::get($default_working_hours, 'content', '')))),
                ],
            ];
        }

        $max_upload_size_kb = max(1, $this->integerValue(config('app.images.default_max_upload_image_size_kb', 5120)));
        $normalized_fields = [];
        $default_fields = (array) Arr::get($defaults, 'contact_form.fields', []);
        $saved_fields = Arr::get($settings, 'contact_form.fields', []);
        $saved_fields = is_array($saved_fields) ? $saved_fields : [];

        foreach ($default_fields as $field_name => $default_field) {
            $default_field = $this->stringKeyedArray($default_field);
            $field = Arr::get($saved_fields, $field_name, []);
            $field = is_array($field) ? $field : [];
            $min_length = Arr::get($field, 'min_length');
            $max_length = Arr::get($field, 'max_length');

            $normalized_fields[$field_name] = [
                'enabled' => (bool) Arr::get($field, 'enabled', Arr::get($default_field, 'enabled', true)),
                'required' => (bool) Arr::get($field, 'enabled', Arr::get($default_field, 'enabled', true))
                    && (bool) Arr::get($field, 'required', Arr::get($default_field, 'required', false)),
                'regex' => $this->normalizeNullableString(Arr::get($field, 'regex')),
                'min_length' => filled($min_length) ? max(0, $this->integerValue($min_length)) : Arr::get($default_field, 'min_length'),
                'max_length' => filled($max_length) ? max(0, $this->integerValue($max_length)) : Arr::get($default_field, 'max_length'),
            ];

            if ($field_name === 'file') {
                $allowed_types = Arr::get($field, 'allowed_types', Arr::get($default_field, 'allowed_types', ['jpg', 'jpeg', 'png']));
                $allowed_types = is_array($allowed_types) ? $allowed_types : ['jpg', 'jpeg', 'png'];

                $normalized_fields[$field_name] += [
                    'max_size_kb' => min(
                        $max_upload_size_kb,
                        max(1, $this->integerValue(Arr::get($field, 'max_size_kb', $max_upload_size_kb))),
                    ),
                    'allowed_types' => collect($allowed_types)
                        ->map(fn (mixed $type): string => Str::lower(Str::trim($this->stringValue($type))))
                        ->filter(fn (string $type): bool => preg_match('/^[a-z0-9]+$/', $type) === 1)
                        ->unique()
                        ->values()
                        ->all() ?: ['jpg', 'jpeg', 'png'],
                    'upload_path' => normalize_upload_path_template($this->stringValue(Arr::get(
                        $field,
                        'upload_path',
                        Arr::get($default_field, 'upload_path', 'images/contacts/{year}/{month}'),
                    ))),
                ];
            }
        }

        $normalized_settings = [
            'localized' => $normalized_localized,
            'images' => $this->normalizeContactsRepeater(Arr::get($settings, 'images', []), 'image'),
            'phones' => $this->normalizeContactsRepeater(Arr::get($settings, 'phones', []), 'phone'),
            'emails' => $this->normalizeContactsRepeater(Arr::get($settings, 'emails', []), 'email'),
            'addresses' => $this->normalizeContactsAddressRepeater(Arr::get($settings, 'addresses', [])),
            'contact_form' => [
                'fields' => $normalized_fields,
                'destinations' => [
                    'email' => [
                        'enabled' => (bool) Arr::get($settings, 'contact_form.destinations.email.enabled', false),
                        'send_file' => (bool) Arr::get($settings, 'contact_form.destinations.email.send_file', false),
                        'address' => Str::trim($this->stringValue(Arr::get($settings, 'contact_form.destinations.email.address', ''))),
                    ],
                    'telegram' => [
                        'enabled' => (bool) Arr::get($settings, 'contact_form.destinations.telegram.enabled', false),
                        'send_file' => (bool) Arr::get($settings, 'contact_form.destinations.telegram.send_file', false),
                        'bot_token' => Str::trim($this->stringValue(Arr::get($settings, 'contact_form.destinations.telegram.bot_token', ''))),
                        'chat_id' => Str::trim($this->stringValue(Arr::get($settings, 'contact_form.destinations.telegram.chat_id', ''))),
                    ],
                ],
            ],
            'email' => [
                'templates' => $this->normalizeLocalizedTemplates(
                    Arr::get($settings, 'email.templates', []),
                    Arr::get($defaults, 'email.templates', []),
                    ['subject', 'body'],
                ),
            ],
            'telegram' => [
                'templates' => $this->normalizeLocalizedTemplates(
                    Arr::get($settings, 'telegram.templates', []),
                    Arr::get($defaults, 'telegram.templates', []),
                    ['body'],
                ),
            ],
            'map' => [
                'iframe' => Str::trim($this->stringValue(Arr::get($settings, 'map.iframe', ''))),
                'latitude' => $this->normalizeNullableDecimal(Arr::get($settings, 'map.latitude')),
                'longitude' => $this->normalizeNullableDecimal(Arr::get($settings, 'map.longitude')),
                'width' => max(1, $this->integerValue(Arr::get($settings, 'map.width', 600))),
                'height' => max(1, $this->integerValue(Arr::get($settings, 'map.height', 400))),
                'custom_css_classes' => Str::squish($this->stringValue(Arr::get($settings, 'map.custom_css_classes', ''))),
            ],
        ];

        $normalized_contract = array_replace_recursive(
            $this->buildContactsSettingsContract($defaults),
            $settings,
            [
                'meta' => ['contract_version' => 1],
                ...$normalized_settings,
            ],
        );

        $normalized_contract['localized'] = $normalized_localized;
        $normalized_contract['images'] = $normalized_settings['images'];
        $normalized_contract['phones'] = $normalized_settings['phones'];
        $normalized_contract['emails'] = $normalized_settings['emails'];
        $normalized_contract['addresses'] = $normalized_settings['addresses'];
        $normalized_contract['contact_form'] = $normalized_settings['contact_form'];
        $normalized_contract['email'] = $normalized_settings['email'];
        $normalized_contract['telegram'] = $normalized_settings['telegram'];
        $normalized_contract['map'] = $normalized_settings['map'];

        return $normalized_contract;
    }

    /**
     * @param  mixed  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeContactsRepeater(mixed $rows, string $type): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $normalized_rows = collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->values()
            ->map(function (array $row, int $index) use ($type): array {
                $normalized = [
                    'sort_order' => max(0, $this->integerValue(Arr::get($row, 'sort_order', ($index + 1) * 10))),
                ];

                if ($type === 'image') {
                    return $normalized + [
                        'path' => Str::trim($this->stringValue(Arr::get($row, 'path', ''))),
                        'width' => max(1, $this->integerValue(Arr::get($row, 'width', 600))),
                        'height' => max(1, $this->integerValue(Arr::get($row, 'height', 600))),
                        'is_square' => (bool) Arr::get($row, 'is_square', true),
                        'background' => $this->normalizeImageBackground(Arr::get($row, 'background', 'transparent')),
                        'custom_css_classes' => Str::squish($this->stringValue(Arr::get($row, 'custom_css_classes', ''))),
                    ];
                }

                if ($type === 'phone') {
                    return $normalized + [
                        'type' => Str::trim($this->stringValue(Arr::get($row, 'type', 'mobile'))),
                        'value' => Str::trim($this->stringValue(Arr::get($row, 'value', ''))),
                    ];
                }

                if ($type === 'email') {
                    return $normalized + [
                        'value' => Str::lower(Str::trim($this->stringValue(Arr::get($row, 'value', '')))),
                    ];
                }

                return $normalized + [
                    'localized' => is_array(Arr::get($row, 'localized')) ? Arr::get($row, 'localized') : [],
                ];
            })
            ->filter(function (array $row) use ($type): bool {
                return ! in_array($type, ['phone', 'email'], true) || filled(Arr::get($row, 'value'));
            })
            ->sortBy('sort_order');

        if ($type === 'email') {
            $normalized_rows = $normalized_rows->unique(
                fn (array $row): string => Str::lower($this->stringValue(Arr::get($row, 'value'))),
            );
        }

        return $normalized_rows->values()->all();
    }

    /**
     * @param mixed $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeContactsAddressRepeater(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $active_languages = (new Language())->getActiveLanguages();

        return collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->values()
            ->map(function (array $row, int $index) use ($active_languages): array {
                $localized = [];

                foreach ($active_languages as $language) {
                    $language_id = $this->stringValue($language->id);
                    $content = Arr::get($row, "localized.$language_id", []);
                    $content = is_array($content) ? $content : [];

                    $localized[$language_id] = [
                        'title' => Str::trim($this->stringValue(Arr::get($content, 'title', ''))),
                        'description' => $this->normalizeNullableString(Arr::get($content, 'description')),
                        'value' => Str::trim($this->stringValue(Arr::get($content, 'value', ''))),
                        'url' => $this->normalizeNullableString(Arr::get($content, 'url')),
                    ];
                }

                return [
                    'sort_order' => max(0, $this->integerValue(Arr::get($row, 'sort_order', ($index + 1) * 10))),
                    'localized' => $localized,
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    private function normalizeImageBackground(mixed $background): string
    {
        $background = Str::trim($this->stringValue($background));

        return $background === 'transparent' || preg_match('/^#[0-9a-fA-F]{6}$/', $background) === 1
            ? $background
            : 'transparent';
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = Str::trim($this->stringValue($value));

        return filled($value) ? $value : null;
    }

    private function normalizeNullableDecimal(mixed $value): ?float
    {
        return filled($value) && is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  mixed  $templates
     * @param  mixed  $defaults
     * @param  array<int, string>  $keys
     * @return array<string, array<string, string>>
     */
    private function normalizeLocalizedTemplates(mixed $templates, mixed $defaults, array $keys): array
    {
        $templates = is_array($templates) ? $templates : [];
        $defaults = is_array($defaults) ? $defaults : [];

        return collect($defaults)->mapWithKeys(function (mixed $default, string $language_id) use ($templates, $keys): array {
            $saved = Arr::get($templates, $language_id, []);
            $saved = is_array($saved) ? $saved : [];
            $default = is_array($default) ? $default : [];

            return [$language_id => collect($keys)->mapWithKeys(fn (string $key): array => [
                $key => Str::trim($this->stringValue(Arr::get($saved, $key, Arr::get($default, $key, '')))),
            ])->all()];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCategorySettingsContract(
        bool $is_sorting_enabled,
        bool $is_filtering_enabled,
        int $products_per_page_limit,
        bool $is_ajax_products_loading_enabled,
        int $product_image_width,
        int $product_image_height,
        int $category_upload_max_size_kb,
        string $category_upload_directory,
        string $category_no_image_path,
        int $category_preview_list_width,
        int $category_preview_list_height,
        int $category_preview_page_width,
        int $category_preview_page_height,
    ): array {
        return [
            'meta' => [
                'contract_version' => 2,
            ],
            'ui' => [
                'sorting' => [
                    'enabled' => $is_sorting_enabled,
                ],
                'filtering' => [
                    'enabled' => $is_filtering_enabled,
                ],
            ],
            'pagination' => [
                'products_per_page_limit' => max(1, $products_per_page_limit),
                'ajax_products_loading_enabled' => $is_ajax_products_loading_enabled,
            ],
            'images' => [
                'products' => [
                    'width' => max(1, $product_image_width),
                    'height' => max(1, $product_image_height),
                ],
            ],
            'header' => [
                'categories' => [],
            ],
            'admin' => [
                'upload' => [
                    'max_size_kb' => max(1, $category_upload_max_size_kb),
                    'directory' => $category_upload_directory,
                ],
                'images' => [
                    'no_image' => [
                        'path' => $category_no_image_path,
                    ],
                    'preview_in_list' => [
                        'width' => max(1, $category_preview_list_width),
                        'height' => max(1, $category_preview_list_height),
                    ],
                    'preview_in_page' => [
                        'width' => max(1, $category_preview_page_width),
                        'height' => max(1, $category_preview_page_height),
                    ],
                ],
            ],
            'items' => [
                'sorting' => [],
                'filters' => [],
            ],
            'localized' => [],
        ];
    }

    /** @param array<string, mixed> $defaults */
    private function syncCategorySettingsContract(PageSetting $page_setting, array $defaults): void
    {
        $settings = $page_setting->settings;

        if (! is_array($settings)) {
            $settings = [];
        }

        $settings = array_replace_recursive(
            $this->buildCategorySettingsContract(
                is_sorting_enabled              : $this->booleanValue(Arr::get($settings, 'ui.sorting.enabled', true)),
                is_filtering_enabled            : $this->booleanValue(Arr::get($settings, 'ui.filtering.enabled', false)),
                products_per_page_limit         : $this->integerValue(Arr::get($settings, 'pagination.products_per_page_limit', $defaults['products_per_page_limit'])),
                is_ajax_products_loading_enabled: $this->booleanValue(Arr::get($settings, 'pagination.ajax_products_loading_enabled', $defaults['ajax_products_loading_enabled'])),
                product_image_width             : $this->integerValue(Arr::get($settings, 'images.products.width', $defaults['product_image_width'])),
                product_image_height            : $this->integerValue(Arr::get($settings, 'images.products.height', $defaults['product_image_height'])),
                category_upload_max_size_kb     : $this->integerValue(Arr::get($settings, 'admin.upload.max_size_kb', $defaults['category_upload_max_size_kb'])),
                category_upload_directory       : $this->stringValue(Arr::get($settings, 'admin.upload.directory', $defaults['category_upload_directory'])),
                category_no_image_path          : $this->stringValue(Arr::get($settings, 'admin.images.no_image.path', $defaults['category_no_image_path'])),
                category_preview_list_width     : $this->integerValue(Arr::get($settings, 'admin.images.preview_in_list.width', $defaults['category_preview_list_width'])),
                category_preview_list_height    : $this->integerValue(Arr::get($settings, 'admin.images.preview_in_list.height', $defaults['category_preview_list_height'])),
                category_preview_page_width     : $this->integerValue(Arr::get($settings, 'admin.images.preview_in_page.width', $defaults['category_preview_page_width'])),
                category_preview_page_height    : $this->integerValue(Arr::get($settings, 'admin.images.preview_in_page.height', $defaults['category_preview_page_height'])),
            ),
            $settings,
        );

        Arr::set($settings, 'meta.contract_version', 2);
        Arr::set($settings, 'ui.sorting.enabled', $this->booleanValue(Arr::get($settings, 'ui.sorting.enabled', true)));
        Arr::set($settings, 'ui.filtering.enabled', $this->booleanValue(Arr::get($settings, 'ui.filtering.enabled', false)));
        Arr::set($settings, 'pagination.products_per_page_limit', max(1, $this->integerValue(Arr::get($settings, 'pagination.products_per_page_limit', $defaults['products_per_page_limit']))));
        Arr::set($settings, 'pagination.ajax_products_loading_enabled', $this->booleanValue(Arr::get($settings, 'pagination.ajax_products_loading_enabled', $defaults['ajax_products_loading_enabled'])));
        Arr::set($settings, 'images.products.width', max(1, $this->integerValue(Arr::get($settings, 'images.products.width', $defaults['product_image_width']))));
        Arr::set($settings, 'images.products.height', max(1, $this->integerValue(Arr::get($settings, 'images.products.height', $defaults['product_image_height']))));
        Arr::set(
            $settings,
            'header.categories',
            collect((array) Arr::get($settings, 'header.categories', []))
                ->filter(fn (mixed $category_id): bool => is_int($category_id) || (is_string($category_id) && is_numeric($category_id)))
                ->map(fn (int|string $category_id): int => (int) $category_id)
                ->filter(fn (int $category_id): bool => $category_id > 0)
                ->unique()
                ->values()
                ->all(),
        );
        Arr::set($settings, 'admin.upload.max_size_kb', max(1, $this->integerValue(Arr::get($settings, 'admin.upload.max_size_kb', $defaults['category_upload_max_size_kb']))));
        Arr::set($settings, 'admin.upload.directory', $this->stringValue(Arr::get($settings, 'admin.upload.directory', $defaults['category_upload_directory'])));
        Arr::set($settings, 'admin.images.no_image.path', $this->stringValue(Arr::get($settings, 'admin.images.no_image.path', $defaults['category_no_image_path'])));
        Arr::set($settings, 'admin.images.preview_in_list.width', max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_list.width', $defaults['category_preview_list_width']))));
        Arr::set($settings, 'admin.images.preview_in_list.height', max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_list.height', $defaults['category_preview_list_height']))));
        Arr::set($settings, 'admin.images.preview_in_page.width', max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_page.width', $defaults['category_preview_page_width']))));
        Arr::set($settings, 'admin.images.preview_in_page.height', max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_page.height', $defaults['category_preview_page_height']))));
        Arr::set($settings, 'items.sorting', $this->normalizeSettingsItems((array) Arr::get($settings, 'items.sorting', [])));
        Arr::set($settings, 'items.filters', $this->normalizeSettingsItems((array) Arr::get($settings, 'items.filters', [])));

        $page_setting->forceFill([
            'settings' => $settings,
        ])->save();
    }

    /**
     * @param  array{
     *     customer_minimum_stock_quantity:int,
     *     customer_image_width:int,
     *     customer_image_height:int,
     *     admin_ean_max_length:int,
     *     admin_upload_max_size_kb:int,
     *     admin_image_upload_directory:string,
     *     admin_no_image_path:string,
     *     admin_preview_in_list_width:int,
     *     admin_preview_in_list_height:int,
     *     admin_preview_in_page_width:int,
     *     admin_preview_in_page_height:int
     * }  $defaults
     * @return array<string, mixed>
     */
    private function buildProductSettingsContract(array $defaults): array
    {
        return [
            'meta' => [
                'contract_version' => 2,
            ],
            'customer' => [
                'stock' => [
                    'minimum_stock_quantity' => $defaults['customer_minimum_stock_quantity'],
                ],
                'images' => [
                    'product' => [
                        'width' => $defaults['customer_image_width'],
                        'height' => $defaults['customer_image_height'],
                    ],
                ],
            ],
            'admin' => [
                'validation' => [
                    'ean_max_length' => $defaults['admin_ean_max_length'],
                ],
                'upload' => [
                    'max_size_kb' => $defaults['admin_upload_max_size_kb'],
                    'directory' => $defaults['admin_image_upload_directory'],
                ],
                'images' => [
                    'no_image' => [
                        'path' => $defaults['admin_no_image_path'],
                    ],
                    'preview_in_list' => [
                        'width' => $defaults['admin_preview_in_list_width'],
                        'height' => $defaults['admin_preview_in_list_height'],
                    ],
                    'preview_in_page' => [
                        'width' => $defaults['admin_preview_in_page_width'],
                        'height' => $defaults['admin_preview_in_page_height'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array{
     *     customer_minimum_stock_quantity:int,
     *     customer_image_width:int,
     *     customer_image_height:int,
     *     admin_ean_max_length:int,
     *     admin_upload_max_size_kb:int,
     *     admin_image_upload_directory:string,
     *     admin_no_image_path:string,
     *     admin_preview_in_list_width:int,
     *     admin_preview_in_list_height:int,
     *     admin_preview_in_page_width:int,
     *     admin_preview_in_page_height:int
     * }  $defaults
     */
    private function syncProductSettingsContract(PageSetting $page_setting, array $defaults): void
    {
        $settings = $page_setting->settings;

        if (! is_array($settings)) {
            $settings = [];
        }

        $settings = array_replace_recursive(
            $this->buildProductSettingsContract([
                'customer_minimum_stock_quantity' => max(0, $this->integerValue(Arr::get($settings, 'customer.stock.minimum_stock_quantity', $defaults['customer_minimum_stock_quantity']))),
                'customer_image_width' => max(1, $this->integerValue(Arr::get($settings, 'customer.images.product.width', Arr::get($settings, 'images.product.width', $defaults['customer_image_width'])))),
                'customer_image_height' => max(1, $this->integerValue(Arr::get($settings, 'customer.images.product.height', Arr::get($settings, 'images.product.height', $defaults['customer_image_height'])))),
                'admin_ean_max_length' => max(1, $this->integerValue(Arr::get($settings, 'admin.validation.ean_max_length', Arr::get($settings, 'validation.ean_max_length', $defaults['admin_ean_max_length'])))),
                'admin_upload_max_size_kb' => max(1, $this->integerValue(Arr::get($settings, 'admin.upload.max_size_kb', $defaults['admin_upload_max_size_kb']))),
                'admin_image_upload_directory' => $this->stringValue(Arr::get($settings, 'admin.upload.directory', $defaults['admin_image_upload_directory'])),
                'admin_no_image_path' => $this->stringValue(Arr::get($settings, 'admin.images.no_image.path', $defaults['admin_no_image_path'])),
                'admin_preview_in_list_width' => max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_list.width', $defaults['admin_preview_in_list_width']))),
                'admin_preview_in_list_height' => max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_list.height', $defaults['admin_preview_in_list_height']))),
                'admin_preview_in_page_width' => max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_page.width', $defaults['admin_preview_in_page_width']))),
                'admin_preview_in_page_height' => max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_page.height', $defaults['admin_preview_in_page_height']))),
            ]),
            $settings,
        );

        $customer_stock_minimum_quantity = max(
            0,
            $this->integerValue(Arr::get(
                $settings,
                'customer.stock.minimum_stock_quantity',
                Arr::get($settings, 'stock.minimum_stock_quantity', $defaults['customer_minimum_stock_quantity']),
            )),
        );

        Arr::set($settings, 'customer.stock.minimum_stock_quantity', $customer_stock_minimum_quantity);
        Arr::set($settings, 'customer.images.product.width', max(1, $this->integerValue(Arr::get($settings, 'customer.images.product.width', $defaults['customer_image_width']))));
        Arr::set($settings, 'customer.images.product.height', max(1, $this->integerValue(Arr::get($settings, 'customer.images.product.height', $defaults['customer_image_height']))));
        Arr::set($settings, 'admin.validation.ean_max_length', max(1, $this->integerValue(Arr::get($settings, 'admin.validation.ean_max_length', Arr::get($settings, 'validation.ean_max_length', $defaults['admin_ean_max_length'])))));
        Arr::set($settings, 'admin.upload.max_size_kb', max(1, $this->integerValue(Arr::get($settings, 'admin.upload.max_size_kb', $defaults['admin_upload_max_size_kb']))));
        Arr::set($settings, 'admin.upload.directory', $this->stringValue(Arr::get($settings, 'admin.upload.directory', $defaults['admin_image_upload_directory'])));
        Arr::set($settings, 'admin.images.no_image.path', $this->stringValue(Arr::get($settings, 'admin.images.no_image.path', $defaults['admin_no_image_path'])));
        Arr::set($settings, 'admin.images.preview_in_list.width', max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_list.width', $defaults['admin_preview_in_list_width']))));
        Arr::set($settings, 'admin.images.preview_in_list.height', max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_list.height', $defaults['admin_preview_in_list_height']))));
        Arr::set($settings, 'admin.images.preview_in_page.width', max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_page.width', $defaults['admin_preview_in_page_width']))));
        Arr::set($settings, 'admin.images.preview_in_page.height', max(1, $this->integerValue(Arr::get($settings, 'admin.images.preview_in_page.height', $defaults['admin_preview_in_page_height']))));
        Arr::set($settings, 'meta.contract_version', 2);
        Arr::forget($settings, ['stock', 'validation']);

        $page_setting->forceFill([
            'settings' => $settings,
        ])->save();
    }

    /**
     * @param  array{products_per_page_limit:int,search_product_width:int,search_product_height:int,search_not_found_path:string,search_not_found_width:int,search_not_found_height:int}  $defaults
     * @return array<string, mixed>
     */
    private function buildSearchSettingsContract(array $defaults): array
    {
        return [
            'meta' => [
                'contract_version' => 2,
            ],
            'ui' => [
                'sorting' => [
                    'enabled' => false,
                ],
                'filtering' => [
                    'enabled' => false,
                ],
            ],
            'pagination' => [
                'products_per_page_limit' => $defaults['products_per_page_limit'],
            ],
            'images' => [
                'search_product' => [
                    'width' => $defaults['search_product_width'],
                    'height' => $defaults['search_product_height'],
                ],
                'search_not_found' => [
                    'path' => $defaults['search_not_found_path'],
                    'width' => $defaults['search_not_found_width'],
                    'height' => $defaults['search_not_found_height'],
                ],
            ],
        ];
    }

    /**
     * @param  array{products_per_page_limit:int,search_product_width:int,search_product_height:int,search_not_found_path:string,search_not_found_width:int,search_not_found_height:int}  $defaults
     */
    private function syncSearchSettingsContract(PageSetting $page_setting, array $defaults): void
    {
        $settings = $page_setting->settings;

        if (! is_array($settings)) {
            $settings = [];
        }

        $settings = array_replace_recursive(
            $this->buildSearchSettingsContract([
                'products_per_page_limit' => max(1, $this->integerValue(Arr::get($settings, 'pagination.products_per_page_limit', $defaults['products_per_page_limit']))),
                'search_product_width' => max(1, $this->integerValue(Arr::get($settings, 'images.search_product.width', $defaults['search_product_width']))),
                'search_product_height' => max(1, $this->integerValue(Arr::get($settings, 'images.search_product.height', $defaults['search_product_height']))),
                'search_not_found_path' => $this->stringValue(Arr::get($settings, 'images.search_not_found.path', $defaults['search_not_found_path'])),
                'search_not_found_width' => max(1, $this->integerValue(Arr::get($settings, 'images.search_not_found.width', $defaults['search_not_found_width']))),
                'search_not_found_height' => max(1, $this->integerValue(Arr::get($settings, 'images.search_not_found.height', $defaults['search_not_found_height']))),
            ]),
            $settings,
        );

        Arr::set($settings, 'pagination.products_per_page_limit', max(1, $this->integerValue(Arr::get($settings, 'pagination.products_per_page_limit', $defaults['products_per_page_limit']))));
        Arr::set($settings, 'images.search_product.width', max(1, $this->integerValue(Arr::get($settings, 'images.search_product.width', $defaults['search_product_width']))));
        Arr::set($settings, 'images.search_product.height', max(1, $this->integerValue(Arr::get($settings, 'images.search_product.height', $defaults['search_product_height']))));
        Arr::set($settings, 'images.search_not_found.path', $this->stringValue(Arr::get($settings, 'images.search_not_found.path', $defaults['search_not_found_path'])));
        Arr::set($settings, 'images.search_not_found.width', max(1, $this->integerValue(Arr::get($settings, 'images.search_not_found.width', $defaults['search_not_found_width']))));
        Arr::set($settings, 'images.search_not_found.height', max(1, $this->integerValue(Arr::get($settings, 'images.search_not_found.height', $defaults['search_not_found_height']))));
        Arr::set($settings, 'meta.contract_version', 2);
        Arr::set($settings, 'ui.sorting.enabled', (bool) Arr::get($settings, 'ui.sorting.enabled', false));
        Arr::set($settings, 'ui.filtering.enabled', (bool) Arr::get($settings, 'ui.filtering.enabled', false));

        $page_setting->forceFill([
            'settings' => $settings,
        ])->save();
    }

    private function syncDefaultSortingItems(PageSetting $page_setting): void
    {
        $settings = is_array($page_setting->settings) ? $page_setting->settings : [];

        $existing_items = collect((array) Arr::get($settings, 'items.sorting', []))
            ->filter(fn (mixed $item): bool => is_array($item) && filled($this->stringValue(Arr::get($item, 'code'))))
            ->mapWithKeys(fn (array $item): array => [$this->stringValue(Arr::get($item, 'code')) => $item]);

        $sorting_items = [];

        foreach ($this->getDefaultSortingItemPayloads() as $index => $item_payload) {
            $code = $this->stringValue($item_payload['code']);
            $existing_item = $existing_items->get($code, []);

            $sorting_items[] = [
                'code' => $code,
                'source_type' => $this->stringValue(Arr::get($existing_item, 'source_type', $item_payload['source_type'])),
                'source_id' => Arr::get($existing_item, 'source_id'),
                'is_enabled' => $this->booleanValue(Arr::get($existing_item, 'is_enabled', true)),
                'sort_order' => $this->integerValue(Arr::get($existing_item, 'sort_order', ($index + 1) * 10)),
                'get' => array_replace_recursive($this->arrayValue($item_payload['get']), $this->arrayValue(Arr::get($existing_item, 'get', []))),
                'config' => array_replace_recursive($this->arrayValue($item_payload['config']), $this->arrayValue(Arr::get($existing_item, 'config', []))),
            ];
        }

        Arr::set($settings, 'items.sorting', $sorting_items);

        $page_setting->forceFill([
            'settings' => $settings,
        ])->save();
    }

    private function syncMissingTranslations(PageSetting $page_setting): void
    {
        $settings = is_array($page_setting->settings) ? $page_setting->settings : [];
        $localized_data = Arr::get($settings, 'localized', []);

        if (! is_array($localized_data)) {
            $localized_data = [];
        }

        $active_languages = (new Language())->getActiveLanguages();

        foreach ($active_languages as $language) {
            $language_id = (string) $language->id;

            if (! isset($localized_data[$language_id]) || ! is_array($localized_data[$language_id])) {
                $localized_data[$language_id] = $this->buildDefaultTranslationContent();

                continue;
            }

            $localized_data[$language_id] = array_replace_recursive(
                $this->buildDefaultTranslationContent(),
                $localized_data[$language_id],
            );
        }

        Arr::set($settings, 'localized', $localized_data);

        $page_setting->forceFill([
            'settings' => $settings,
        ])->save();
    }

    private function syncFilterItemsNode(PageSetting $page_setting): void
    {
        $settings = is_array($page_setting->settings) ? $page_setting->settings : [];

        Arr::set($settings, 'items.filters', $this->normalizeSettingsItems((array) Arr::get($settings, 'items.filters', [])));

        $page_setting->forceFill([
            'settings' => $settings,
        ])->save();
    }

    /**
     * @return array<int, array{code: string, source_type: string, get: array<string, mixed>, config: array<string, mixed>}>
     */
    private function getDefaultSortingItemPayloads(): array
    {
        return [
            [
                'code' => 'default',
                'source_type' => 'static',
                'get' => ['key' => 'sort', 'value' => 'default', 'extra' => []],
                'config' => ['selection' => 'single'],
            ],
            [
                'code' => 'newest',
                'source_type' => 'static',
                'get' => ['key' => 'sort', 'value' => 'newest', 'extra' => []],
                'config' => ['selection' => 'single'],
            ],
            [
                'code' => 'bestsellers',
                'source_type' => 'static',
                'get' => ['key' => 'sort', 'value' => 'bestsellers', 'extra' => []],
                'config' => ['selection' => 'single'],
            ],
            [
                'code' => 'price-asc',
                'source_type' => 'static',
                'get' => ['key' => 'sort', 'value' => 'price-asc', 'extra' => []],
                'config' => ['selection' => 'single'],
            ],
            [
                'code' => 'price-desc',
                'source_type' => 'static',
                'get' => ['key' => 'sort', 'value' => 'price-desc', 'extra' => []],
                'config' => ['selection' => 'single'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildDefaultTranslationContent(): array
    {
        return [
            'sorting' => [
                'title' => '',
                'description' => '',
            ],
        ];
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSettingsItems(array $items): array
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) && filled($this->stringValue(Arr::get($item, 'code'))))
            ->map(function (array $item): array {
                $item_get = $this->stringKeyedArray(Arr::get($item, 'get', []));

                return [
                    'code' => $this->stringValue(Arr::get($item, 'code', '')),
                    'is_enabled' => (bool) Arr::get($item, 'is_enabled', true),
                    'sort_order' => max(0, $this->integerValue(Arr::get($item, 'sort_order', 0))),
                    'source_type' => $this->stringValue(Arr::get($item, 'source_type')) ?: null,
                    'source_id' => Arr::get($item, 'source_id'),
                    'get' => [
                        'key' => $this->stringValue(Arr::get($item_get, 'key', '')),
                        'value' => Arr::get($item_get, 'value'),
                        'extra' => is_array(Arr::get($item_get, 'extra')) ? Arr::get($item_get, 'extra') : [],
                    ],
                    'config' => is_array(Arr::get($item, 'config')) ? Arr::get($item, 'config') : [],
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function booleanValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /** @return array<string, mixed> */
    private function stringKeyedArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return array<int|string, mixed> */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
