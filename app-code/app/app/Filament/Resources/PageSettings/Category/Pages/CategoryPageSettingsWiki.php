<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Category\Pages;

use App\Filament\Resources\PageSettings\Category\CategoryPageSettingResource;
use Filament\Resources\Pages\Page;

class CategoryPageSettingsWiki extends Page
{
    protected static string $resource = CategoryPageSettingResource::class;

    protected string $view = 'filament.resources.page-settings.category.pages.category-page-settings-wiki';

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string
    {
        return __('admin/settings/category_page_settings.wiki.title');
    }

    public function getHeading(): string
    {
        return __('admin/settings/category_page_settings.wiki.title');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'admin/page-settings'                        => __('admin/default.menu.item_page_settings'),
            CategoryPageSettingResource::getUrl('index') => __('admin/settings/category_page_settings.navigation_label'),
            CategoryPageSettingResource::getUrl('wiki')  => __('admin/settings/category_page_settings.wiki.navigation_label'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getWikiSections(): array
    {
        return [
            [
                'title'                  => __('admin/settings/category_page_settings.wiki.sections.sorting.title'),
                'description'            => __('admin/settings/category_page_settings.wiki.sections.sorting.description'),
                'screenshot_url'         => $this->getWikiScreenshotUrl('sorting-tab.png'),
                'screenshot_relative'    => 'images/wiki/category-page-settings/sorting-tab.png',
                'screenshot_description' => __('admin/settings/category_page_settings.wiki.sections.sorting.screenshot_description'),
                'fields'                 => [
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.is_sorting_enabled'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.is_sorting_enabled.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.is_sorting_enabled.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.is_sorting_enabled.example'),
                    ],
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.sorting_items'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.sorting_items.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.sorting_items.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.sorting_items.example'),
                    ],
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.selection_mode'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.selection_mode.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.selection_mode.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.selection_mode.example'),
                    ],
                ],
            ],
            [
                'title'                  => __('admin/settings/category_page_settings.wiki.sections.filters.title'),
                'description'            => __('admin/settings/category_page_settings.wiki.sections.filters.description'),
                'screenshot_url'         => $this->getWikiScreenshotUrl('filters-tab.png'),
                'screenshot_relative'    => 'images/wiki/category-page-settings/filters-tab.png',
                'screenshot_description' => __('admin/settings/category_page_settings.wiki.sections.filters.screenshot_description'),
                'fields'                 => [
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.is_filtering_enabled'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.is_filtering_enabled.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.is_filtering_enabled.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.is_filtering_enabled.example'),
                    ],
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.get_key') . ' / ' . __('admin/settings/category_page_settings.labels.get_value'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.get_contract.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.get_contract.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.get_contract.example'),
                    ],
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.get_extra'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.get_extra.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.get_extra.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.get_extra.example'),
                    ],
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.filter_mode'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.filter_mode.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.filter_mode.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.filter_mode.example'),
                    ],
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.min_price') . ' / ' . __('admin/settings/category_page_settings.labels.max_price') . ' / ' . __('admin/settings/category_page_settings.labels.step'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.price_range.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.price_range.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.price_range.example'),
                    ],
                ],
            ],
            [
                'title'                  => __('admin/settings/category_page_settings.wiki.sections.localized.title'),
                'description'            => __('admin/settings/category_page_settings.wiki.sections.localized.description'),
                'screenshot_url'         => $this->getWikiScreenshotUrl('localized-tab.png'),
                'screenshot_relative'    => 'images/wiki/category-page-settings/localized-tab.png',
                'screenshot_description' => __('admin/settings/category_page_settings.wiki.sections.localized.screenshot_description'),
                'fields'                 => [
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.sorting_title') . ' / ' . __('admin/settings/category_page_settings.labels.sorting_description'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.sorting_content.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.sorting_content.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.sorting_content.example'),
                    ],
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.filters_title') . ' / ' . __('admin/settings/category_page_settings.labels.filters_description'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.filter_content.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.filter_content.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.filter_content.example'),
                    ],
                    [
                        'label'   => __('admin/settings/category_page_settings.labels.filters_drawer_title') . ' / ' . __('admin/settings/category_page_settings.labels.filters_apply_button_text') . ' / ' . __('admin/settings/category_page_settings.labels.filters_clear_button_text'),
                        'purpose' => __('admin/settings/category_page_settings.wiki.fields.drawer_content.purpose'),
                        'how'     => __('admin/settings/category_page_settings.wiki.fields.drawer_content.how'),
                        'example' => __('admin/settings/category_page_settings.wiki.fields.drawer_content.example'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getPracticalExamples(): array
    {
        return [
            __('admin/settings/category_page_settings.wiki.examples.price_range'),
            __('admin/settings/category_page_settings.wiki.examples.stock_toggle'),
            __('admin/settings/category_page_settings.wiki.examples.attribute_multi'),
        ];
    }

    private function getWikiScreenshotUrl(string $file_name): ?string
    {
        $relative_path = 'images/wiki/category-page-settings/' . $file_name;

        if (! file_exists(public_path($relative_path))) {
            return null;
        }

        return asset($relative_path);
    }
}
