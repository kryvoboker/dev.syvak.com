<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Models\PageSettings\PageSetting;
use App\Models\PageSettings\PageSettingItem;
use App\Models\PageSettings\PageSettingTranslation;
use App\Models\ApplicationSettings\Language;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class PageSettingsBootstrapService
{
    public function bootstrapCategoryPageSetting(): PageSetting
    {
        try {
            $page_setting = PageSetting::query()->firstOrCreate(
                [
                    'page_type' => PageSetting::PAGE_TYPE_CATEGORY,
                ],
                [
                    'is_sorting_enabled'   => true,
                    'is_filtering_enabled' => true,
                    'settings'             => $this->buildSettingsContract(true, true),
                ],
            );

            $this->syncSettingsContract($page_setting);
            $this->syncDefaultSortingItems($page_setting);
            $this->syncMissingTranslations($page_setting);

            Log::channel('daily')->info('Category page setting bootstrapped.', [
                'page_setting_id'      => (int) $page_setting->id,
                'page_type'            => (string) $page_setting->page_type,
                'sorting_items_count'  => (int) $page_setting->sortingItems()->count(),
                'translations_count'   => (int) $page_setting->translations()->count(),
                'is_sorting_enabled'   => (bool) $page_setting->is_sorting_enabled,
                'is_filtering_enabled' => (bool) $page_setting->is_filtering_enabled,
            ]);

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
    private function buildSettingsContract(bool $is_sorting_enabled, bool $is_filtering_enabled): array
    {
        return [
            'meta' => [
                'contract_version' => 1,
            ],
            'ui' => [
                'sorting' => [
                    'enabled' => $is_sorting_enabled,
                ],
                'filters' => [
                    'enabled' => $is_filtering_enabled,
                ],
            ],
        ];
    }

    private function syncSettingsContract(PageSetting $page_setting): void
    {
        $settings = $page_setting->settings;

        if (! is_array($settings)) {
            $settings = [];
        }

        $settings = array_replace_recursive(
            $this->buildSettingsContract(
                (bool) $page_setting->is_sorting_enabled,
                (bool) $page_setting->is_filtering_enabled,
            ),
            $settings,
        );

        Arr::set($settings, 'ui.sorting.enabled', (bool) $page_setting->is_sorting_enabled);
        Arr::set($settings, 'ui.filters.enabled', (bool) $page_setting->is_filtering_enabled);
        Arr::set($settings, 'meta.contract_version', 1);

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
            'filters' => [
                'title'             => '',
                'description'       => '',
                'drawer_title'      => '',
                'apply_button_text' => '',
                'clear_button_text' => '',
            ],
            'option_labels' => [
                'default'     => '',
                'newest'      => '',
                'bestsellers' => '',
                'price_asc'   => '',
                'price_desc'  => '',
                'price'       => '',
                'stock'       => '',
            ],
        ];
    }
}
