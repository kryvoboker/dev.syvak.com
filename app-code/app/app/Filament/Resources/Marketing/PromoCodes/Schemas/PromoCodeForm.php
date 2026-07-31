<?php

declare(strict_types=1);

namespace App\Filament\Resources\Marketing\PromoCodes\Schemas;

use App\Enums\Marketing\PromoCodeDiscountBaseModeEnum;
use App\Enums\Marketing\PromoCodeDiscountTypeEnum;
use App\Enums\Marketing\PromoCodeLimitModeEnum;
use App\Enums\Marketing\PromoCodeTypeEnum;
use App\Models\ApplicationSettings\Language;
use App\Services\Marketing\PromoCodeAdminOptionsService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PromoCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        $active_languages = (new Language())->getActiveLanguages();

        return $schema
            ->components([
                Tabs::make('PromoCodeTabs')
                    ->tabs([
                        self::generalTab(),
                        self::usersTab(),
                        self::productsAndCategoriesTab(),
                        self::errorsTab($active_languages),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    private static function generalTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/marketing/promo_codes.tabs.general'))
            ->schema([
                Section::make(__('admin/default.sections.basic_info'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin/marketing/promo_codes.labels.name'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label(__('admin/marketing/promo_codes.labels.code'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.code'))
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label(__('admin/marketing/promo_codes.labels.is_active'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.is_active'))
                            ->default(true)
                            ->required(),
                        Radio::make('promo_type')
                            ->label(__('admin/marketing/promo_codes.labels.promo_type'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.promo_type'))
                            ->options(self::typeOptions())
                            ->default(PromoCodeTypeEnum::Regular->value)
                            ->live()
                            ->required(),
                        Radio::make('discount_type')
                            ->label(__('admin/marketing/promo_codes.labels.discount_type'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.discount_type'))
                            ->options(self::discountTypeOptions())
                            ->default(PromoCodeDiscountTypeEnum::Percentage->value)
                            ->live()
                            ->required(),
                        Radio::make('discount_base_mode')
                            ->label(__('admin/marketing/promo_codes.labels.discount_base_mode'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.discount_base_mode'))
                            ->options(self::discountBaseModeOptions())
                            ->default(PromoCodeDiscountBaseModeEnum::IncludeDiscountedProductsAtRrp->value)
                            ->visible(fn (Get $get): bool => $get('promo_type') !== PromoCodeTypeEnum::Super->value)
                            ->required(fn (Get $get): bool => $get('promo_type') !== PromoCodeTypeEnum::Super->value),
                    ])
                    ->columns(2),
                Section::make(__('admin/marketing/promo_codes.labels.discounts'))
                    ->schema([
                        Repeater::make('discount_items')
                            ->label(__('admin/marketing/promo_codes.labels.discounts'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.discounts'))
                            ->schema([
                                Select::make('currency_id')
                                    ->label(__('admin/marketing/promo_codes.labels.currency'))
                                    ->helperText(__('admin/marketing/promo_codes.helpers.currency'))
                                    ->options(fn (): array => app(PromoCodeAdminOptionsService::class)->currencyOptions())
                                    ->searchable()
                                    ->required()
                                    ->distinct(),
                                TextInput::make('value')
                                    ->label(__('admin/marketing/promo_codes.labels.value'))
                                    ->helperText(__('admin/marketing/promo_codes.helpers.value'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->default(fn (): array => [[
                                'currency_id' => app(PromoCodeAdminOptionsService::class)->defaultCurrencyId(),
                            ]])
                            ->addActionLabel(__('admin/default.actions.add'))
                            ->reorderable(false)
                            ->rules(['array']),
                    ]),
                Section::make(__('admin/default.sections.basic_info'))
                    ->schema([
                        TextInput::make('global_usage_limit')
                            ->label(__('admin/marketing/promo_codes.labels.global_usage_limit'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.global_usage_limit'))
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),
                        TextInput::make('minimum_order_amount')
                            ->label(__('admin/marketing/promo_codes.labels.minimum_order_amount'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.minimum_order_amount'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        DateTimePicker::make('starts_at')
                            ->label(__('admin/marketing/promo_codes.labels.starts_at'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.starts_at'))
                            ->seconds(false)
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->label(__('admin/marketing/promo_codes.labels.ends_at'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.ends_at'))
                            ->seconds(false)
                            ->after('starts_at')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function usersTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/marketing/promo_codes.tabs.users'))
            ->schema([
                Section::make(__('admin/marketing/promo_codes.labels.users'))
                    ->schema([
                        Radio::make('user_limit_scope')
                            ->label(__('admin/marketing/promo_codes.labels.user_limit_scope'))
                            ->options(self::userScopeOptions())
                            ->default('all_users')
                            ->live()
                            ->required(),
                        Repeater::make('selected_users')
                            ->label(__('admin/marketing/promo_codes.labels.users'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.users_or_groups'))
                            ->schema([
                                Select::make('user_id')
                                    ->label(__('admin/marketing/promo_codes.labels.user_id'))
                                    ->searchable()
                                    ->searchDebounce(1000)
                                    ->getSearchResultsUsing(function (Get $get, string $search): array {
                                        return self::searchUserOptions($search, $get, 'user_id');
                                    })
                                    ->getOptionLabelUsing(fn (int|string|null $value): ?string => app(PromoCodeAdminOptionsService::class)->userLabelById($value))
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->rules(['integer', 'distinct'])
                                    ->required(),
                            ])
                            ->visible(fn (Get $get): bool => $get('user_limit_scope') === 'selected_users')
                            ->defaultItems(0)
                            ->addActionLabel(__('admin/default.actions.add'))
                            ->reorderable(false),
                        Radio::make('user_limit_mode')
                            ->label(__('admin/marketing/promo_codes.labels.user_limit_mode'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.user_limit_mode'))
                            ->options(self::limitModeOptions())
                            ->default(PromoCodeLimitModeEnum::PerEntity->value)
                            ->live()
                            ->visible(fn (Get $get): bool => $get('user_limit_scope') === 'selected_users' && filled($get('selected_users')))
                            ->nullable(),
                        TextInput::make('user_usage_limit')
                            ->label(__('admin/marketing/promo_codes.labels.user_usage_limit'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.user_usage_limit'))
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => $get('user_limit_scope') === 'selected_users' && filled($get('selected_users')))
                            ->nullable(),
                        TextInput::make('all_users_usage_limit')
                            ->label(__('admin/marketing/promo_codes.labels.all_users_usage_limit'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.all_users_usage_limit'))
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => $get('user_limit_scope') === 'all_users')
                            ->nullable(),
                    ])
                    ->columns(2),
                Section::make(__('admin/marketing/promo_codes.labels.user_groups'))
                    ->schema([
                        Radio::make('group_limit_scope')
                            ->label(__('admin/marketing/promo_codes.labels.group_limit_scope'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.group_limit_scope'))
                            ->options(self::groupScopeOptions())
                            ->default('all_groups')
                            ->live()
                            ->required(),
                        Repeater::make('selected_user_groups')
                            ->label(__('admin/marketing/promo_codes.labels.user_groups'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.users_or_groups'))
                            ->schema([
                                Select::make('user_group_id')
                                    ->label(__('admin/marketing/promo_codes.labels.user_group_id'))
                                    ->searchable()
                                    ->searchDebounce(1000)
                                    ->getSearchResultsUsing(function (Get $get, string $search): array {
                                        return self::searchUserOptions($search, $get, 'user_group_id');
                                    })
                                    ->getOptionLabelUsing(fn (int|string|null $value): ?string => app(PromoCodeAdminOptionsService::class)->userGroupLabelById($value))
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->rules(['integer', 'distinct'])
                                    ->required(),
                            ])
                            ->visible(fn (Get $get): bool => $get('group_limit_scope') === 'selected_groups')
                            ->defaultItems(0)
                            ->addActionLabel(__('admin/default.actions.add'))
                            ->reorderable(false),
                        Radio::make('group_limit_mode')
                            ->label(__('admin/marketing/promo_codes.labels.group_limit_mode'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.group_limit_mode'))
                            ->options(self::limitModeOptions())
                            ->default(PromoCodeLimitModeEnum::PerEntity->value)
                            ->live()
                            ->visible(fn (Get $get): bool => $get('group_limit_scope') === 'selected_groups' && filled($get('selected_user_groups')))
                            ->nullable(),
                        TextInput::make('group_usage_limit')
                            ->label(__('admin/marketing/promo_codes.labels.group_usage_limit'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.group_usage_limit'))
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => $get('group_limit_scope') === 'selected_groups' && filled($get('selected_user_groups')))
                            ->nullable(),
                        TextInput::make('all_groups_usage_limit')
                            ->label(__('admin/marketing/promo_codes.labels.all_groups_usage_limit'))
                            ->helperText(__('admin/marketing/promo_codes.helpers.all_groups_usage_limit'))
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => $get('group_limit_scope') === 'all_groups')
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function productsAndCategoriesTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/marketing/promo_codes.tabs.products_categories'))
            ->schema([
                Section::make(__('admin/marketing/promo_codes.labels.products'))
                    ->schema([
                        Repeater::make('product_items')
                            ->schema([
                                Select::make('product_id')
                                    ->label(__('admin/marketing/promo_codes.labels.products'))
                                    ->searchable()
                                    ->searchDebounce(1000)
                                    ->getSearchResultsUsing(function (string $search, Get $get): array {
                                        return self::searchCatalogOptions($search, $get, 'product_id');
                                    })
                                    ->getOptionLabelUsing(fn (int|string|null $value): ?string => app(PromoCodeAdminOptionsService::class)->productLabelById($value))
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->rules(['integer', 'distinct'])
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel(__('admin/default.actions.add'))
                            ->reorderable(false),
                    ]),
                Section::make(__('admin/marketing/promo_codes.labels.categories'))
                    ->schema([
                        Repeater::make('category_items')
                            ->schema([
                                Select::make('category_id')
                                    ->label(__('admin/marketing/promo_codes.labels.categories'))
                                    ->searchable()
                                    ->searchDebounce(1000)
                                    ->getSearchResultsUsing(function (string $search, Get $get): array {
                                        return self::searchCatalogOptions($search, $get, 'category_id');
                                    })
                                    ->getOptionLabelUsing(fn (int|string|null $value): ?string => app(PromoCodeAdminOptionsService::class)->categoryLabelById($value))
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->rules(['integer', 'distinct'])
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel(__('admin/default.actions.add'))
                            ->reorderable(false),
                    ]),
            ]);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    private static function errorsTab(Collection $active_languages): Tabs\Tab
    {
        $language_tabs = $active_languages->map(function (Language $language): Tabs\Tab {
            return Tabs\Tab::make($language->name)
                ->badge($language->code)
                ->schema([
                    Textarea::make("error_messages.{$language->id}.expired_message")
                        ->label(__('admin/marketing/promo_codes.labels.expired_message'))
                        ->helperText(__('admin/marketing/promo_codes.helpers.error_fallback'))
                        ->nullable(),
                    Textarea::make("error_messages.{$language->id}.minimum_order_message")
                        ->label(__('admin/marketing/promo_codes.labels.minimum_order_message'))
                        ->helperText(__('admin/marketing/promo_codes.helpers.error_fallback'))
                        ->nullable(),
                    Textarea::make("error_messages.{$language->id}.usage_limit_message")
                        ->label(__('admin/marketing/promo_codes.labels.usage_limit_message'))
                        ->helperText(__('admin/marketing/promo_codes.helpers.error_fallback'))
                        ->nullable(),
                ]);
        })->all();

        return Tabs\Tab::make(__('admin/marketing/promo_codes.tabs.errors'))
            ->schema([
                Tabs::make('PromoCodeErrorLanguageTabs')
                    ->tabs($language_tabs)
                    ->contained(false),
            ])
            ->visible($language_tabs !== []);
    }

    /**
     * @param array<int, string> $values
     * @return array<string, string>
     */
    private static function enumOptions(array $values, string $translation_group): array
    {
        return collect($values)->mapWithKeys(fn (string $value): array => [
            $value => __("admin/marketing/promo_codes.{$translation_group}.{$value}"),
        ])->all();
    }

    /** @return array<string, string> */
    private static function typeOptions(): array
    {
        return self::enumOptions(array_column(PromoCodeTypeEnum::cases(), 'value'), 'types');
    }

    /** @return array<string, string> */
    private static function discountTypeOptions(): array
    {
        return self::enumOptions(array_column(PromoCodeDiscountTypeEnum::cases(), 'value'), 'types');
    }

    /** @return array<string, string> */
    private static function discountBaseModeOptions(): array
    {
        return self::enumOptions(array_column(PromoCodeDiscountBaseModeEnum::cases(), 'value'), 'modes');
    }

    /** @return array<string, string> */
    private static function limitModeOptions(): array
    {
        return self::enumOptions(array_column(PromoCodeLimitModeEnum::cases(), 'value'), 'modes');
    }

    /** @return array<string, string> */
    private static function userScopeOptions(): array
    {
        return [
            'all_users' => __('admin/marketing/promo_codes.modes.all_users'),
            'selected_users' => __('admin/marketing/promo_codes.modes.selected_users'),
        ];
    }

    /** @return array<string, string> */
    private static function groupScopeOptions(): array
    {
        return [
            'all_groups' => __('admin/marketing/promo_codes.modes.all_groups'),
            'selected_groups' => __('admin/marketing/promo_codes.modes.selected_groups'),
        ];
    }

    private static function searchUserOptions(string $search, Get $get, string $field): array
    {
        if (Str::length($search) < 3) {
            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/default.errors.min_search_length', ['length' => 3]))
                ->danger()
                ->send();

            return [];
        }

        $repeater_name = $field === 'user_id' ? 'selected_users' : 'selected_user_groups';
        $selected_ids = collect($get('../../' . $repeater_name))
            ->pluck($field)
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return $field === 'user_id'
            ? app(PromoCodeAdminOptionsService::class)->userOptions($search, $selected_ids)
            : app(PromoCodeAdminOptionsService::class)->userGroupSearchOptions($search, $selected_ids);
    }

    private static function searchCatalogOptions(string $search, Get $get, string $field): array
    {
        if (Str::length($search) < 3) {
            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/default.errors.min_search_length', ['length' => 3]))
                ->danger()
                ->send();

            return [];
        }

        $selected_ids = collect($get('../../' . ($field === 'product_id' ? 'product_items' : 'category_items')))
            ->pluck($field)
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return $field === 'product_id'
            ? app(PromoCodeAdminOptionsService::class)->productSearchOptions($search, $selected_ids)
            : app(PromoCodeAdminOptionsService::class)->categorySearchOptions($search, $selected_ids);
    }
}
