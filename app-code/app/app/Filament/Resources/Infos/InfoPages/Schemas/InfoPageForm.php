<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Schemas;

use App\Enums\PositionInPageEnum;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Filament\Resources\Trait\MetaTextFormTrait;
use App\Filament\Resources\Trait\SlugFormTrait;
use App\Filament\Resources\Trait\SortOrderFormTrait;
use App\Filament\Resources\Trait\ToggleCheckboxFormTrait;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InfoPageForm
{
    use LanguageTrait, SlugFormTrait, MetaTextFormTrait, ToggleCheckboxFormTrait, SortOrderFormTrait;

    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getAcriveLanguages();

        return $schema
            ->components([
                Tabs::make('InfoPagesTabs')
                    ->tabs([
                        self::createGeneralTab(),
                        self::createTranslationsTabs($active_languages),
                        self::createMetaTextsTabs($active_languages),
                        self::createSlugsTabs($active_languages),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Tabs\Tab
     */
    protected static function createGeneralTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/default.tabs.general'))
            ->schema([
                Section::make(__('admin/default.sections.basic_info'))
                    ->schema([
                        Select::make('positions')
                            ->label(__('admin/infos/info_pages.columns.position'))
                            ->multiple()
                            ->options(function () {
                                $positions = [];

                                foreach (PositionInPageEnum::cases() as $position) {
                                    $positions[$position->value] = Str::ucfirst($position->value);
                                }

                                return $positions;
                            })
                            ->nullable()
                            ->placeholder(__('admin/infos/info_pages.placeholders.position'))
                            ->helperText(__('admin/infos/info_pages.helpers.position')),

                        self::getIsActiveField(),

                        self::getSortOrderField(),

                        self::getIsNoIndexField(),
                    ])
            ]);
    }
}
