<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\CatalogFilter\Schemas;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Models\ApplicationSettings\Language;
use Closure;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class CatalogFilterSetForm
{
    public static function configure(Schema $schema): Schema
    {
        $active_languages = (new Language())->getActiveLanguages();
        $price_filter_group_name = CatalogFilterGroupSourceTypeEnum::Price->value;

        return $schema
            ->components([
                Tabs::make('CatalogFilterSetTabs')
                    ->tabs([
                        Tabs\Tab::make(__('admin/catalogs/catalog-filter/catalog-filter-set.tabs.core'))
                            ->schema([
                                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.sections.general'))
                                    ->schema([
                                        TextInput::make('code')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.code'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->disabled()
                                            ->dehydrated(),

                                        Select::make('context_types')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.context_type'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->multiple()
                                            ->options(self::getContextOptions())
                                            ->searchable()
                                            ->preload()
                                            ->rules(['required', 'array'])
                                            ->rule(function (): Closure {
                                                return function (string $attribute, mixed $value, Closure $fail): void {
                                                    if (! is_array($value)) {
                                                        return;
                                                    }

                                                    $invalid_contexts = collect($value)
                                                        ->map(fn (mixed $context): string => (string) $context)
                                                        ->diff(array_keys(self::getContextOptions()));

                                                    if ($invalid_contexts->isNotEmpty()) {
                                                        $fail(__('admin/catalogs/catalog-filter/catalog-filter-set.errors.invalid_context_type'));
                                                    }
                                                };
                                            })
                                            ->required(),

                                        Toggle::make('is_enabled')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.is_enabled'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->default(true),

                                        Toggle::make('is_price_filter_enabled')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.is_price_filter_enabled'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->default(true),

                                        Toggle::make('is_attribute_filtering_enabled')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.is_attribute_filtering_enabled'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->default(true),
                                    ])
                                    ->columns(),

                                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.sections.strategy_by_price'))
                                    ->schema([
                                        Select::make('price_source_mode')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.price_source_mode'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->options(self::getPriceSourceModeOptions())
                                            ->rules([
                                                'required', Rule::in(array_keys(self::getPriceSourceModeOptions())),
                                            ])
                                            ->required(),

                                        Select::make('discount_only_policy')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.discount_only_policy'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->options(self::getDiscountOnlyPolicyOptions())
                                            ->rules([
                                                'required', Rule::in(array_keys(self::getDiscountOnlyPolicyOptions())),
                                            ])
                                            ->required(),

                                        Select::make('facet_strategy')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.facet_strategy'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->options(self::getFacetStrategyOptions())
                                            ->rules([
                                                'required', Rule::in(array_keys(self::getFacetStrategyOptions())),
                                            ])
                                            ->required(),
                                    ])
                                    ->columns(),

                                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.sections.strategy_by_stock'))
                                    ->schema([
                                        TextInput::make('min_stock_quantity')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.min_stock_quantity'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),
                                    ])
                                    ->columns(),

                                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.sections.rebuild'))
                                    ->schema([
                                        TextInput::make('settings.rebuild_chunk_size')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.rebuild_chunk_size'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),

                                        TextInput::make('settings.rebuild_lock_timeout_seconds')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.rebuild_lock_timeout_seconds'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),

                                        TextInput::make('settings.max_selected_values_per_group')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.max_selected_values_per_group'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),

                                        Toggle::make('settings.base_currency_indexing_required')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.base_currency_indexing_required'))
                                            ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                            ->default(true),
                                    ])
                                    ->columns(),

                                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.sections.index_meta'))
                                    ->schema([
                                        TextEntry::make('index_meta_active_index_version')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.active_index_version'))
                                            ->state(fn (callable $get): string => (string) ($get('index_meta.active_index_version') ?? '—')),

                                        TextEntry::make('index_meta_building_index_version')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.building_index_version'))
                                            ->state(fn (callable $get): string => (string) ($get('index_meta.building_index_version') ?? '—')),

                                        TextEntry::make('index_meta_last_status')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.last_status'))
                                            ->state(fn (callable $get): string => (string) ($get('index_meta.last_status') ?? '—')),

                                        TextEntry::make('index_meta_last_run_mode')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.last_run_mode'))
                                            ->state(fn (callable $get): string => (string) ($get('index_meta.last_run_mode') ?? '—')),

                                        TextEntry::make('index_meta_last_progress_percent')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.last_progress_percent'))
                                            ->state(fn (callable $get): string => (string) ($get('index_meta.last_progress_percent') ?? '—')),

                                        TextEntry::make('index_meta_index_rows_total')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.index_rows_total'))
                                            ->state(fn (callable $get): string => (string) ($get('index_meta.index_rows_total') ?? '—')),

                                        TextEntry::make('index_meta_last_full_rebuild_at')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.last_full_rebuild_at'))
                                            ->state(fn (callable $get): string => (string) ($get('index_meta.last_full_rebuild_at') ?? '—')),

                                        TextEntry::make('index_meta_last_incremental_sync_at')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.last_incremental_sync_at'))
                                            ->state(fn (callable $get): string => (string) ($get('index_meta.last_incremental_sync_at') ?? '—')),

                                        TextEntry::make('index_meta_rebuild_lock_key')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.rebuild_lock_key'))
                                            ->state(fn (callable $get): string => (string) ($get('index_meta.rebuild_lock_key') ?? '—')),

                                        TextEntry::make('index_meta_rebuild_lock_acquired_at')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.rebuild_lock_acquired_at'))
                                            ->state(fn (callable $get): string => (string) ($get('index_meta.rebuild_lock_acquired_at') ?? '—')),
                                    ])
                                    ->columns(),
                            ]),

                        Tabs\Tab::make(__('admin/catalogs/catalog-filter/catalog-filter-set.tabs.filter_options'))
                            ->schema([
                                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.sections.filter_options'))
                                    ->schema([
                                        Repeater::make('filter_items')
                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.filter_items'))
                                            ->schema([
                                                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.option_labels'))
                                                    ->schema([
                                                        self::buildOptionLabelTabs($active_languages, 'catalog_filter_option_labels_tabs'),
                                                    ])
                                                    ->columnSpanFull(),

                                                TextInput::make('code')
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.code'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                                    ->disabled()
                                                    ->dehydrated(),

                                                TextInput::make('source_type')
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.source_type'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->rule(function (Get $get): Closure {
                                                        return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                                            $source_type = (string) $value;
                                                            $source_id = $get('source_id');

                                                            if ($source_type === 'attribute' && (int) $source_id <= 0) {
                                                                $fail(__('admin/catalogs/catalog-filter/catalog-filter-set.errors.invalid_source_id'));
                                                            }

                                                            if ($source_type === 'price' && filled($source_id)) {
                                                                $fail(__('admin/catalogs/catalog-filter/catalog-filter-set.errors.invalid_source_id'));
                                                            }
                                                        };
                                                    }),

                                                TextInput::make('source_id')
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.source_id'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                                    ->disabled()
                                                    ->dehydrated(),

                                                Toggle::make('is_enabled')
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.is_enabled'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                                    ->default(true),

                                                TextInput::make('sort_order')
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.sort_order'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->required(),

                                                TextInput::make('get.key')
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.get_key'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                                    ->required(),

                                                TextInput::make('get.value')
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.get_value'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.get_value_usage')),

                                                Grid::make()
                                                    ->columns(1)
                                                    ->schema([
                                                        Select::make('config.mode')
                                                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.filter_mode'))
                                                            ->helperText(function (callable $get): string {
                                                                $config_mode = (string) $get('config.mode');
                                                                $helper_text = __('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki');

                                                                if (blank($config_mode)) {
                                                                    return $helper_text;
                                                                }

                                                                return self::getFilterModeDescription($config_mode) . ' ' . $helper_text;
                                                            })
                                                            ->options(self::getFilterModeOptions())
                                                            ->hint(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.filter_mode_hint'))
                                                            ->searchable()
                                                            ->rules([
                                                                'required',
                                                                Rule::in(array_keys(self::getFilterModeOptions())),
                                                            ])
                                                            ->required(),
                                                    ]),

                                                KeyValue::make('get.extra')
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.get_extra'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                                    ->keyLabel(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.key'))
                                                    ->valueLabel(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.value'))
                                                    ->columnSpanFull(),

                                                TextInput::make('config.min_price')
                                                    ->numeric()
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.min_price'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                                    ->minValue(0)
                                                    ->rule(function (Get $get): Closure {
                                                        return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                                            $max_price = $get('config.max_price');

                                                            if (is_numeric($value) && is_numeric($max_price) && (float) $value > (float) $max_price) {
                                                                $fail(__('admin/catalogs/catalog-filter/catalog-filter-set.errors.min_price_greater_than_max'));
                                                            }
                                                        };
                                                    })
                                                    ->visible(fn (callable $get): bool => (string) $get('code') === $price_filter_group_name)
                                                    ->dehydrated(fn (callable $get): bool => (string) $get('code') === $price_filter_group_name),

                                                TextInput::make('config.max_price')
                                                    ->numeric()
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.max_price'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                                    ->minValue(0)
                                                    ->rule(function (Get $get): Closure {
                                                        return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                                            $min_price = $get('config.min_price');

                                                            if (is_numeric($value) && is_numeric($min_price) && (float) $value < (float) $min_price) {
                                                                $fail(__('admin/catalogs/catalog-filter/catalog-filter-set.errors.max_price_less_than_min'));
                                                            }
                                                        };
                                                    })
                                                    ->visible(fn (callable $get): bool => (string) $get('code') === $price_filter_group_name)
                                                    ->dehydrated(fn (callable $get): bool => (string) $get('code') === $price_filter_group_name),

                                                TextInput::make('config.step')
                                                    ->numeric()
                                                    ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.step'))
                                                    ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki'))
                                                    ->minValue(0.0001)
                                                    ->visible(fn (callable $get): bool => (string) $get('code') === $price_filter_group_name)
                                                    ->dehydrated(fn (callable $get): bool => (string) $get('code') === $price_filter_group_name),
                                            ])
                                            ->defaultItems(0)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->collapsible()
                                            ->collapsed(false)
                                            ->columns(),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->activeTab(1)
                    ->persistTabInQueryString()
                    ->contained(false)
                    ->columnSpanFull(),
            ]);
    }

    private static function buildOptionLabelTabs($active_languages, string $tabs_name): Tabs
    {
        $tabs = [];

        foreach ($active_languages as $language) {
            $language_code = (string) $language->code;

            $tabs[] = Tabs\Tab::make($language->name)
                ->badge($language_code)
                ->schema([
                    TextInput::make("config.labels.$language_code")
                        ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.option_label_value'))
                        ->helperText(__('admin/catalogs/catalog-filter/catalog-filter-set.helpers.see_wiki')),
                ]);
        }

        return Tabs::make($tabs_name)
            ->tabs($tabs)
            ->activeTab(1)
            ->contained(false);
    }

    /**
     * @return array<string, string>
     */
    private static function getContextOptions(): array
    {
        return (array) config('catalog-filter.contexts', []);
    }

    /**
     * @return array<string, string>
     */
    private static function getPriceSourceModeOptions(): array
    {
        return (array) config('catalog-filter.price_source_modes', []);
    }

    /**
     * @return array<string, string>
     */
    private static function getDiscountOnlyPolicyOptions(): array
    {
        return (array) config('catalog-filter.discount_only_policies', []);
    }

    /**
     * @return array<string, string>
     */
    private static function getFacetStrategyOptions(): array
    {
        return (array) config('catalog-filter.facet_strategies', []);
    }

    /**
     * @return array<string, string>
     */
    private static function getFilterModeOptions(): array
    {
        return collect((array) config('catalog-filter.filter_modes', []))
            ->keys()
            ->mapWithKeys(function (mixed $mode_value): array {
                $mode = (string) $mode_value;

                return [
                    $mode => (string) __('admin/catalogs/catalog-filter/catalog-filter-set.filter_mode_options.' . $mode),
                ];
            })
            ->all();
    }

    private static function getFilterModeDescription(string $mode): string
    {
        if (! array_key_exists($mode, self::getFilterModeOptions())) {
            return '';
        }

        return (string) __('admin/catalogs/catalog-filter/catalog-filter-set.filter_mode_descriptions.' . $mode);
    }
}
