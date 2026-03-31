<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Models\ApplicationSettings\Language;
use App\Models\PageSettings\PageSetting;
use App\Models\PageSettings\PageSettingItem;
use App\Models\PageSettings\PageSettingTranslation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class PageSettingsBootstrapService
{
    /**
     * @throws Throwable
     */
    public function bootstrapCategoryPageSetting(): PageSetting
    {
        try {
            $default_products_limit = (int) config('app.page_settings.category.products_per_page_limit', 20);
            $default_ajax_enabled   = (bool) config('app.page_settings.category.ajax_products_loading_enabled', true);

            $page_setting = PageSetting::query()->firstOrCreate(
                [
                    'page_type' => PageSetting::PAGE_TYPE_CATEGORY,
                ],
                [
                    'is_sorting_enabled'   => true,
                    'is_filtering_enabled' => false,
                    'settings'             => $this->buildSettingsContract(
                        is_sorting_enabled: true,
                        products_per_page_limit: $default_products_limit,
                        is_ajax_products_loading_enabled: $default_ajax_enabled,
                    ),
                ],
            );

            $this->syncSettingsContract($page_setting);
            $this->syncDefaultSortingItems($page_setting);
            $this->syncMissingTranslations($page_setting);
            $this->purgeLegacyFilterItems($page_setting);

            return $page_setting->fresh(['translations.language', 'items']) ?? $page_setting;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Category page setting bootstrap failed.', [
                'page_type' => PageSetting::PAGE_TYPE_CATEGORY,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSettingsContract(
        bool $is_sorting_enabled,
        int $products_per_page_limit,
        bool $is_ajax_products_loading_enabled,
    ): array {
        return [
            'meta' => [
                'contract_version' => 1,
            ],
            'ui' => [
                'sorting' => [
                    'enabled' => $is_sorting_enabled,
                ],
            ],
            'pagination' => [
                'products_per_page_limit'       => max(1, $products_per_page_limit),
                'ajax_products_loading_enabled' => $is_ajax_products_loading_enabled,
            ],
        ];
    }

    private function syncSettingsContract(PageSetting $page_setting): void
    {
        $settings = $page_setting->settings;

        if (! is_array($settings)) {
            $settings = [];
        }

        $default_products_limit = (int) config('app.page_settings.category.products_per_page_limit', 20);
        $default_ajax_enabled   = (bool) config('app.page_settings.category.ajax_products_loading_enabled', true);

        $settings = array_replace_recursive(
            $this->buildSettingsContract(
                is_sorting_enabled: (bool) $page_setting->is_sorting_enabled,
                products_per_page_limit: (int) Arr::get($settings, 'pagination.products_per_page_limit', $default_products_limit),
                is_ajax_products_loading_enabled: (bool) Arr::get($settings, 'pagination.ajax_products_loading_enabled', $default_ajax_enabled),
            ),
            $settings,
        );

        Arr::set($settings, 'ui.sorting.enabled', (bool) $page_setting->is_sorting_enabled);
        Arr::set($settings, 'pagination.products_per_page_limit', max(1, (int) Arr::get($settings, 'pagination.products_per_page_limit', $default_products_limit)));
        Arr::set($settings, 'pagination.ajax_products_loading_enabled', (bool) Arr::get($settings, 'pagination.ajax_products_loading_enabled', $default_ajax_enabled));
        Arr::set($settings, 'meta.contract_version', 1);
        Arr::forget($settings, ['ui.filters']);

        $page_setting->forceFill([
            'settings' => $settings,
        ])->save();
    }

    private function syncDefaultSortingItems(PageSetting $page_setting): void
    {
        foreach ($this->getDefaultSortingItemPayloads() as $index => $item_payload) {
            $item = PageSettingItem::query()->firstOrNew([
                'page_setting_id' => (int) $page_setting->id,
                'type'            => PageSetting::ITEM_TYPE_SORTING,
                'code'            => $item_payload['code'],
            ]);

            $item->fill([
                'source_type' => $item_payload['source_type'],
                'source_id'   => null,
                'get'         => $item_payload['get'],
                'config'      => $item_payload['config'],
            ]);

            if (! $item->exists) {
                $item->fill([
                    'is_enabled' => true,
                    'sort_order' => ($index + 1) * 10,
                ]);
            }

            $item->save();
        }
    }

    private function syncMissingTranslations(PageSetting $page_setting): void
    {
        $active_languages = new Language()->getActiveLanguages();

        foreach ($active_languages as $language) {
            PageSettingTranslation::query()->firstOrCreate(
                [
                    'page_setting_id' => (int) $page_setting->id,
                    'language_id'     => (int) $language->id,
                ],
                [
                    'content' => $this->buildDefaultTranslationContent(),
                ],
            );
        }
    }

    private function purgeLegacyFilterItems(PageSetting $page_setting): void
    {
        PageSettingItem::query()
            ->where('page_setting_id', (int) $page_setting->id)
            ->where('type', PageSetting::ITEM_TYPE_FILTER)
            ->delete();
    }

    /**
     * @return array<int, array{code: string, source_type: string, get: array<string, mixed>, config: array<string, mixed>}>
     */
    private function getDefaultSortingItemPayloads(): array
    {
        return [
            [
                'code'        => 'default',
                'source_type' => 'static',
                'get'         => ['key' => 'sort', 'value' => 'default', 'extra' => []],
                'config'      => ['selection' => 'single'],
            ],
            [
                'code'        => 'newest',
                'source_type' => 'static',
                'get'         => ['key' => 'sort', 'value' => 'newest', 'extra' => []],
                'config'      => ['selection' => 'single'],
            ],
            [
                'code'        => 'bestsellers',
                'source_type' => 'static',
                'get'         => ['key' => 'sort', 'value' => 'bestsellers', 'extra' => []],
                'config'      => ['selection' => 'single'],
            ],
            [
                'code'        => 'price_asc',
                'source_type' => 'static',
                'get'         => ['key' => 'sort', 'value' => 'price_asc', 'extra' => []],
                'config'      => ['selection' => 'single'],
            ],
            [
                'code'        => 'price_desc',
                'source_type' => 'static',
                'get'         => ['key' => 'sort', 'value' => 'price_desc', 'extra' => []],
                'config'      => ['selection' => 'single'],
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
                'title'       => '',
                'description' => '',
            ],
        ];
    }
}
