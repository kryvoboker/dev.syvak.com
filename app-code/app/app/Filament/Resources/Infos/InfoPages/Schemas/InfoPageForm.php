<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Schemas;

use App\Enums\PositionInPageEnum;
use App\Filament\Resources\Trait\Forms\MetaTextFormTrait;
use App\Filament\Resources\Trait\Forms\SlugFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InfoPageForm
{
    use LanguageTrait;
    use MetaTextFormTrait;
    use SlugFormTrait;

    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getAcriveLanguages();

        return $schema
            ->components([
                Tabs::make('InfoPagesTabs')
                    ->tabs([
                        self::createGeneralTab(),
                        self::createTranslationsFormTabs($active_languages),
                        self::createMetaTextsFormTabs($active_languages),
                        self::createSlugsFormTabs($active_languages),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    protected static function createGeneralTab(): Tabs\Tab
    {
        $positions = collect(PositionInPageEnum::cases())
            ->mapWithKeys(fn (PositionInPageEnum $position) => [
                $position->value => Str::ucfirst($position->value),
            ])->toArray();

        return Tabs\Tab::make(__('admin/default.tabs.general'))
            ->schema([
                Section::make(__('admin/default.sections.basic_info'))
                    ->schema([
                        Select::make('positions')
                            ->label(__('admin/infos/info_pages.columns.position'))
                            ->placeholder(__('admin/infos/info_pages.placeholders.position'))
                            ->helperText(__('admin/infos/info_pages.helpers.position'))
                            ->multiple()
                            ->options($positions)
                            ->searchable()
                            ->nullable()
                            ->preload(false)
                            ->live(false)
                            ->rules(['nullable', 'array', Rule::in(array_keys($positions))]),

                        Toggle::make('is_active')
                            ->label(__('admin/default.labels.is_active'))
                            ->default(true)
                            ->required(),

                        TextInput::make('sort_order')
                            ->label(__('admin/default.labels.sort_order'))
                            ->numeric()
                            ->rules(['numeric', 'min:0'])
                            ->default(1)
                            ->required(),

                        Toggle::make('is_noindex')
                            ->label(__('admin/default.labels.is_noindex'))
                            ->default(false)
                            ->required(),
                    ]),
            ]);
    }
}
