<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Filament;

use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Validation\Rule;
use Modules\ProductsCarousel\Services\ProductsCarouselCategoryTreeService;
use Modules\ProductsCarousel\Services\ProductsCarouselProductSearchService;
use Modules\ProductsCarousel\Support\ProductsCarouselConfig;

/**
 * Defines module-specific Filament form components for ProductsCarousel instance settings.
 */
readonly class ModuleInstanceFormSchema
{
    public function __construct(
        private ProductsCarouselConfig               $products_carousel_config,
        private ProductsCarouselCategoryTreeService  $products_carousel_category_tree_service,
        private ProductsCarouselProductSearchService $products_carousel_product_search_service,
    ) {}

    /**
     * @return array<int, Component>
     */
    public function getComponents(?ModuleDefinition $definition = null, ?ModuleInstance $instance = null): array
    {
        return [
            Section::make()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->columnSpanFull()
                        ->label(__('admin/modules/module_instances.products_carousel.labels.module_name'))
                        ->maxLength(255)
                        ->required(),

                    Select::make('settings.shared.page_types')
                        ->label(__('admin/modules/module_instances.products_carousel.labels.page_types'))
                        ->multiple()
                        ->options($this->getPageTypeOptions())
                        ->required()
                        ->native(false)
                        ->helperText(__('admin/modules/module_instances.products_carousel.helpers.page_types')),

                    Grid::make()
                        ->columns()
                        ->columnSpanFull()
                        ->schema([
                            Select::make('placement')
                                ->label(__('admin/modules/module_instances.labels.placement'))
                                ->options(config('app.modules_placements', []))
                                ->rules([
                                    'required',
                                    Rule::in(array_keys(config('app.modules_placements', []))),
                                ])
                                ->required()
                                ->native(false),

                            TextInput::make('sort_order')
                                ->label(__('admin/modules/module_instances.labels.sort_order'))
                                ->numeric()
                                ->default(1),
                        ]),

                    Toggle::make('is_enabled')
                        ->columnSpanFull()
                        ->label(__('admin/modules/module_instances.labels.is_enabled'))
                        ->default(true),

                    Hidden::make('context_key')
                        ->default(null),

                    Section::make(__('admin/modules/module_instances.products_carousel.sections.shared'))
                        ->description(__('admin/modules/module_instances.products_carousel.helpers.shared'))
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('settings.shared.module_name_for_user')
                                ->label(__('admin/modules/module_instances.products_carousel.labels.module_name_for_user'))
                                ->maxLength(255)
                                ->helperText(__('admin/modules/module_instances.products_carousel.helpers.module_name_for_user')),

                            Textarea::make('settings.shared.short_description_for_user')
                                ->label(__('admin/modules/module_instances.products_carousel.labels.short_description_for_user'))
                                ->rows(3)
                                ->maxLength(1000)
                                ->helperText(__('admin/modules/module_instances.products_carousel.helpers.short_description_for_user')),
                        ]),

                    Section::make(__('admin/modules/module_instances.products_carousel.sections.source_mode'))
                        ->columnSpanFull()
                        ->schema([
                            Radio::make('settings.source_mode')
                                ->label(__('admin/modules/module_instances.products_carousel.labels.source_mode'))
                                ->options([
                                    'category_based' => __('admin/modules/module_instances.products_carousel.options.source_mode.category_based'),
                                    'manual_only'    => __('admin/modules/module_instances.products_carousel.options.source_mode.manual_only'),
                                ])
                                ->default((string) $this->products_carousel_config->get('settings.default_source_mode', 'category_based'))
                                ->live()
                                ->inline(false)
                                ->required(),
                        ]),

                    Section::make(__('admin/modules/module_instances.products_carousel.sections.category_based_window'))
                        ->description(__('admin/modules/module_instances.products_carousel.helpers.category_based_window'))
                        ->columnSpanFull()
                        ->visible(function (callable $get): bool {
                            return (string) $get('settings.source_mode') === 'category_based';
                        })
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Toggle::make('settings.category_based.select_all_categories')
                                        ->label(__('admin/modules/module_instances.products_carousel.actions.select_all_categories'))
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateUpdated(function ($state, callable $set): void {
                                            if ((bool) $state === false) {
                                                return;
                                            }

                                            $set(
                                                'settings.category_based.category_ids',
                                                $this->products_carousel_category_tree_service->getAllActiveCategoryIds(),
                                            );
                                            $set('settings.category_based.select_all_categories', false);
                                            $set('settings.category_based.clear_all_categories', false);
                                        }),

                                    Toggle::make('settings.category_based.clear_all_categories')
                                        ->label(__('admin/modules/module_instances.products_carousel.actions.clear_all_categories'))
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateUpdated(function ($state, callable $set): void {
                                            if ((bool) $state === false) {
                                                return;
                                            }

                                            $set('settings.category_based.category_ids', []);
                                            $set('settings.category_based.selected_product_ids', []);
                                            $set('settings.category_based.clear_all_categories', false);
                                            $set('settings.category_based.select_all_categories', false);
                                        }),
                                ]),

                            CheckboxList::make('settings.category_based.category_ids')
                                ->label(__('admin/modules/module_instances.products_carousel.labels.categories_tree'))
                                ->options($this->products_carousel_category_tree_service->getCheckboxTreeOptions())
                                ->columns(1)
                                ->gridDirection('row')
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                    $filtered_product_ids = $this->products_carousel_product_search_service->filterActiveProductIdsByCategories(
                                        (array) $get('settings.category_based.selected_product_ids'),
                                        (array) $state,
                                    );

                                    $set('settings.category_based.selected_product_ids', $filtered_product_ids);
                                }),

                            Toggle::make('settings.category_based.use_selected_products_only')
                                ->label(__('admin/modules/module_instances.products_carousel.labels.use_selected_products_only'))
                                ->default(false)
                                ->live(),

                            Section::make(__('admin/modules/module_instances.products_carousel.sections.category_products'))
                                ->visible(function (callable $get): bool {
                                    return (bool) $get('settings.category_based.use_selected_products_only') === true;
                                })
                                ->columnSpanFull()
                                ->schema([
                                    Select::make('settings.category_based.search_product_id')
                                        ->label(__('admin/modules/module_instances.products_carousel.labels.search_products_in_selected_categories'))
                                        ->helperText(__('admin/modules/module_instances.products_carousel.helpers.search_products_in_selected_categories'))
                                        ->searchable()
                                        ->live(debounce: 300)
                                        ->dehydrated(false)
                                        ->getSearchResultsUsing(function (string $search, callable $get): array {
                                            return $this->products_carousel_product_search_service->searchActiveByCategories(
                                                $search,
                                                (array) $get('settings.category_based.category_ids'),
                                            );
                                        })
                                        ->getOptionLabelUsing(function ($value): ?string {
                                            if (! is_numeric($value)) {
                                                return null;
                                            }

                                            return $this->products_carousel_product_search_service->getLabelById((int) $value);
                                        })
                                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                            if (! is_numeric($state)) {
                                                return;
                                            }

                                            $selected_product_ids = collect((array) $get('settings.category_based.selected_product_ids'))
                                                ->map(fn (mixed $id): int => (int) $id)
                                                ->push((int) $state)
                                                ->filter(fn (int $id): bool => $id > 0)
                                                ->unique()
                                                ->values()
                                                ->all();

                                            $selected_product_ids = $this->products_carousel_product_search_service->filterActiveProductIdsByCategories(
                                                $selected_product_ids,
                                                (array) $get('settings.category_based.category_ids'),
                                            );

                                            $set('settings.category_based.selected_product_ids', $selected_product_ids);
                                            $set('settings.category_based.search_product_id', null);
                                        }),

                                    CheckboxList::make('settings.category_based.selected_product_ids')
                                        ->label(__('admin/modules/module_instances.products_carousel.labels.selected_products'))
                                        ->helperText(__('admin/modules/module_instances.products_carousel.helpers.selected_products'))
                                        ->options(function (callable $get): array {
                                            return $this->products_carousel_product_search_service->getLabelsByIds(
                                                (array) $get('settings.category_based.selected_product_ids'),
                                            );
                                        })
                                        ->columns(1)
                                        ->gridDirection('row')
                                        ->live(),
                                ]),
                        ]),

                    Section::make(__('admin/modules/module_instances.products_carousel.sections.manual_only_window'))
                        ->description(__('admin/modules/module_instances.products_carousel.helpers.manual_only_window'))
                        ->columnSpanFull()
                        ->visible(function (callable $get): bool {
                            return (string) $get('settings.source_mode') === 'manual_only';
                        })
                        ->schema([
                            Select::make('settings.manual_only.search_product_id')
                                ->label(__('admin/modules/module_instances.products_carousel.labels.search_all_active_products'))
                                ->helperText(__('admin/modules/module_instances.products_carousel.helpers.search_all_active_products'))
                                ->searchable()
                                ->live(debounce: 300)
                                ->dehydrated(false)
                                ->getSearchResultsUsing(function (string $search): array {
                                    return $this->products_carousel_product_search_service->searchAllActive($search);
                                })
                                ->getOptionLabelUsing(function ($value): ?string {
                                    if (! is_numeric($value)) {
                                        return null;
                                    }

                                    return $this->products_carousel_product_search_service->getLabelById((int) $value);
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                    if (! is_numeric($state)) {
                                        return;
                                    }

                                    $selected_product_ids = collect((array) $get('settings.manual_only.selected_product_ids'))
                                        ->map(fn (mixed $id): int => (int) $id)
                                        ->push((int) $state)
                                        ->filter(fn (int $id): bool => $id > 0)
                                        ->unique()
                                        ->values()
                                        ->all();

                                    $selected_product_ids = $this->products_carousel_product_search_service->filterActiveProductIds($selected_product_ids);

                                    $set('settings.manual_only.selected_product_ids', $selected_product_ids);
                                    $set('settings.manual_only.search_product_id', null);
                                }),

                            CheckboxList::make('settings.manual_only.selected_product_ids')
                                ->label(__('admin/modules/module_instances.products_carousel.labels.selected_products'))
                                ->helperText(__('admin/modules/module_instances.products_carousel.helpers.selected_products'))
                                ->options(function (callable $get): array {
                                    return $this->products_carousel_product_search_service->getLabelsByIds(
                                        (array) $get('settings.manual_only.selected_product_ids'),
                                    );
                                })
                                ->columns(1)
                                ->gridDirection('row')
                                ->live(),
                        ]),
                ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getPageTypeOptions(): array
    {
        /** @var array<string, string> $page_types */
        $page_types = config('page-type', []);

        return collect($page_types)
            ->mapWithKeys(fn (string $value, string $key): array => [$value => ucfirst(str_replace('_', ' ', $key))])
            ->all();
    }
}
