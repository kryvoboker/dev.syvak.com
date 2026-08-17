<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Schemas\Components;

use App\Models\ApplicationSettings\Language;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Collection;

class CareTabSchema
{
    /**
     * @param  Collection<int, Language>  $active_languages
     */
    public static function make(Collection $active_languages): Tab
    {
        return Tab::make(__('admin/catalogs/products/products.tabs.care'))
            ->schema([
                Section::make(__('admin/catalogs/products/products.sections.care_block'))
                    ->schema([
                        Tabs::make('ProductCareLanguageTabs')
                            ->tabs(self::buildLanguageTabs($active_languages))
                            ->activeTab(1)
                            ->contained(false)
                            ->persistTabInQueryString()
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  Collection<int, Language>  $active_languages
     * @return array<int, Tab>
     */
    private static function buildLanguageTabs(Collection $active_languages): array
    {
        return $active_languages
            ->map(function (Language $language): Tab {
                $section_path = "composition_and_care_data.translations.$language->id.care";

                return Tab::make((string) $language->name)
                    ->badge((string) $language->code)
                    ->schema([
                        TextInput::make("$section_path.title")
                            ->label(__('admin/catalogs/products/products.labels.care_title'))
                            ->default(__('storefront/default.product.details.care'))
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Repeater::make("$section_path.items")
                            ->label(__('admin/catalogs/products/products.labels.care_items'))
                            ->schema([
                                TextInput::make('value')
                                    ->label(__('admin/catalogs/products/products.labels.care_item_value'))
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->columns(1)
                            ->columnSpanFull(),
                    ])
                    ->columns(1);
            })
            ->values()
            ->all();
    }
}
