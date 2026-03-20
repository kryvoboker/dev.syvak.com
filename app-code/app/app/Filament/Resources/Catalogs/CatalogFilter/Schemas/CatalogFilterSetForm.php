<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\CatalogFilter\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class CatalogFilterSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.sections.general'))
                    ->schema([
                        TextInput::make('code')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.code'))
                            ->disabled()
                            ->dehydrated(),

                        Select::make('context_types')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.context_type'))
                            ->multiple()
                            ->options(self::getContextOptions())
                            ->searchable()
                            ->preload()
                            ->rules(['required', 'array'])
                            ->required(),

                        Toggle::make('is_enabled')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.is_enabled'))
                            ->default(true),

                        Toggle::make('is_price_filter_enabled')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.is_price_filter_enabled'))
                            ->default(true),

                        Toggle::make('is_attribute_filtering_enabled')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.is_attribute_filtering_enabled'))
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.sections.strategy'))
                    ->schema([
                        Select::make('price_source_mode')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.price_source_mode'))
                            ->options(self::getPriceSourceModeOptions())
                            ->rules(['required', Rule::in(array_keys(self::getPriceSourceModeOptions()))])
                            ->required(),

                        Select::make('discount_only_policy')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.discount_only_policy'))
                            ->options(self::getDiscountOnlyPolicyOptions())
                            ->rules(['required', Rule::in(array_keys(self::getDiscountOnlyPolicyOptions()))])
                            ->required(),

                        Select::make('facet_strategy')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.facet_strategy'))
                            ->options(self::getFacetStrategyOptions())
                            ->rules(['required', Rule::in(array_keys(self::getFacetStrategyOptions()))])
                            ->required(),

                        TextInput::make('min_stock_quantity')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.min_stock_quantity'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.sections.rebuild'))
                    ->schema([
                        TextInput::make('settings.rebuild_chunk_size')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.rebuild_chunk_size'))
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        TextInput::make('settings.rebuild_lock_timeout_seconds')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.rebuild_lock_timeout_seconds'))
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        TextInput::make('settings.max_selected_values_per_group')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.max_selected_values_per_group'))
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Toggle::make('settings.base_currency_indexing_required')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.base_currency_indexing_required'))
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make(__('admin/catalogs/catalog-filter/catalog-filter-set.sections.index_meta'))
                    ->schema([
                        Placeholder::make('index_meta_active_index_version')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.active_index_version'))
                            ->content(fn (callable $get): string => (string) ($get('index_meta.active_index_version') ?? '—')),

                        Placeholder::make('index_meta_building_index_version')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.building_index_version'))
                            ->content(fn (callable $get): string => (string) ($get('index_meta.building_index_version') ?? '—')),

                        Placeholder::make('index_meta_last_status')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.last_status'))
                            ->content(fn (callable $get): string => (string) ($get('index_meta.last_status') ?? '—')),

                        Placeholder::make('index_meta_last_run_mode')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.last_run_mode'))
                            ->content(fn (callable $get): string => (string) ($get('index_meta.last_run_mode') ?? '—')),

                        Placeholder::make('index_meta_last_progress_percent')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.last_progress_percent'))
                            ->content(fn (callable $get): string => (string) ($get('index_meta.last_progress_percent') ?? '—')),

                        Placeholder::make('index_meta_index_rows_total')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.index_rows_total'))
                            ->content(fn (callable $get): string => (string) ($get('index_meta.index_rows_total') ?? '—')),

                        Placeholder::make('index_meta_last_full_rebuild_at')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.last_full_rebuild_at'))
                            ->content(fn (callable $get): string => (string) ($get('index_meta.last_full_rebuild_at') ?? '—')),

                        Placeholder::make('index_meta_last_incremental_sync_at')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.last_incremental_sync_at'))
                            ->content(fn (callable $get): string => (string) ($get('index_meta.last_incremental_sync_at') ?? '—')),

                        Placeholder::make('index_meta_rebuild_lock_key')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.rebuild_lock_key'))
                            ->content(fn (callable $get): string => (string) ($get('index_meta.rebuild_lock_key') ?? '—')),

                        Placeholder::make('index_meta_rebuild_lock_acquired_at')
                            ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.labels.rebuild_lock_acquired_at'))
                            ->content(fn (callable $get): string => (string) ($get('index_meta.rebuild_lock_acquired_at') ?? '—')),
                    ])
                    ->columns(2),
            ]);
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
}
