<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Schemas;

use App\Enums\PositionInPageEnum;
use App\Filament\Resources\Trait\Forms\MetaTextFormTrait;
use App\Filament\Resources\Trait\Forms\SelectFormTrait;
use App\Filament\Resources\Trait\Forms\SlugFormTrait;
use App\Filament\Resources\Trait\Forms\SortOrderFormTrait;
use App\Filament\Resources\Trait\Forms\ToggleCheckboxFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InfoPageForm
{
    use LanguageTrait, SlugFormTrait, MetaTextFormTrait,
        ToggleCheckboxFormTrait, SortOrderFormTrait, SelectFormTrait;

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

    /**
     * @return Tabs\Tab
     */
    protected static function createGeneralTab(): Tabs\Tab
    {
        $positions = collect(PositionInPageEnum::cases())
            ->mapWithKeys(fn(PositionInPageEnum $position) => [
                $position->value => Str::ucfirst($position->value),
            ])->toArray();

        return Tabs\Tab::make(__('admin/default.tabs.general'))
            ->schema([
                Section::make(__('admin/default.sections.basic_info'))
                    ->schema([
                        self::getMultipleSelectFormField([
                            'field_name'  => 'positions',
                            'label'       => __('admin/infos/info_pages.columns.position'),
                            'placeholder' => __('admin/infos/info_pages.placeholders.position'),
                            'helper_text' => __('admin/infos/info_pages.helpers.position'),
                            'options'     => $positions,
                            'rules'       => ['array', Rule::in(array_keys($positions))],
                        ]),

                        self::getIsActiveFormField(),

                        self::getSortOrderFormField(),

                        self::getIsNoIndexFormField(),
                    ])
            ]);
    }
}
