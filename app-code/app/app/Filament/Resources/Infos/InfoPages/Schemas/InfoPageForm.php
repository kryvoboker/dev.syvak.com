<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Schemas;

use App\Filament\Resources\Trait\LanguageTrait;
use App\Filament\Resources\Trait\MetaTextTrait;
use App\Filament\Resources\Trait\SlugTrait;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InfoPageForm
{
    use LanguageTrait, SlugTrait, MetaTextTrait;

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
                        Select::make('position')
                            ->label(__('admin/infos/info_pages.columns.position'))
                            ->options(function () {
                                $positions = [];

                                foreach (config('app.positions_in_page') as $position) {
                                    $positions[$position] = Str::ucfirst($position);
                                }

                                return $positions;
                            })
                            ->nullable()
                            ->placeholder(__('admin/infos/info_pages.placeholders.position'))
                            ->helperText(__('admin/infos/info_pages.helpers.position')),

                        Toggle::make('is_active')
                            ->label(__('admin/default.labels.is_active'))
                            ->default(true)
                            ->required(),

                        TextInput::make('sort_order')
                            ->label(__('admin/default.labels.sort_order'))
                            ->numeric()
                            ->default(1)
                            ->required(),

                        Toggle::make('is_noindex')
                            ->label(__('admin/default.labels.is_noindex'))
                            ->default(false)
                            ->required(),
                    ])
            ]);
    }
}
