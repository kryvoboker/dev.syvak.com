<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Marketing\PromoCodeDiscountTypeEnum;
use App\Models\ApplicationSettings\Currency;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Orders\Orders;
use App\Models\Orders\OrderStatuses;
use App\Models\Payment\PaymentStatuses;
use App\Models\Users\User;
use App\Services\Marketing\PromoCodeAdminOptionsService;
use App\Services\Order\OrderAdminDeliveryService;
use App\Services\Order\OrderAdminOptionsService;
use App\Supports\Services\Currency\ConvertPrice;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('OrderTabs')
                    ->tabs([
                        self::generalTab(),
                        self::promoCodeTab(),
                        self::customerTab(),
                        self::shippingTab(),
                        self::paymentsTab(),
                        self::productsTab(),
                        self::totalsTab(),
                        self::currencyTab(),
                        self::historyTab(),
                        self::technicalTab(),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    private static function generalTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.general'))
            ->schema([
                Section::make(__('admin/orders/orders.sections.general'))
                    ->schema([
                        TextInput::make('id')
                            ->label(__('admin/orders/orders.labels.id'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('order_number')
                            ->label(__('admin/orders/orders.labels.order_number'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('order_status_name')
                            ->label(__('admin/orders/orders.labels.order_status'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('order_type')
                            ->label(__('admin/orders/orders.labels.order_type'))
                            ->formatStateUsing(function (mixed $state): string {
                                $order_type = $state instanceof CartModeEnum ? $state->value : (string)$state;

                                return app(OrderAdminOptionsService::class)->getOrderTypeLabel($order_type);
                            })
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('customer.no_call')
                            ->label(__('admin/orders/orders.labels.no_call'))
                            ->options([
                                '0' => __('admin/orders/orders.options.no_call.no'),
                                '1' => __('admin/orders/orders.options.no_call.yes'),
                            ])
                            ->required()
                            ->native(false),
                        SchemaActions::make([
                            RestoreAction::make('restoreOrder')
                                ->label(__('admin/orders/orders.actions.restore'))
                                ->after(function (Orders $record): void {
                                    Log::channel('daily')->info('[OrderForm] order restored', [
                                        'admin_user_id' => auth()->id(),
                                        'order_id' => $record->getKey(),
                                        'order_number' => $record->order_number,
                                    ]);
                                }),
                        ]),
                        Textarea::make('comment')
                            ->label(__('admin/orders/orders.labels.comment'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(),
            ]);
    }

    private static function customerTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.customer'))
            ->schema([
                Section::make(__('admin/orders/orders.sections.customer'))
                    ->schema([
                        Select::make('customer.user_id')
                            ->label(__('admin/orders/orders.labels.user'))
                            ->getSearchResultsUsing(fn (string $search): array => self::searchUsers($search))
                            ->getOptionLabelUsing(fn (int|string|null $value): ?string => self::getUserLabel($value))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, int|string|null $state): void {
                                self::fillCustomerFromUser($set, $state);
                            }),
                        Select::make('customer.user_group_id')
                            ->label(__('admin/orders/orders.labels.user_group'))
                            ->options(fn (): array => app(OrderAdminOptionsService::class)->getUserGroupOptions())
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('customer.first_name')
                            ->label(__('admin/orders/orders.labels.first_name'))
                            ->required(),
                        TextInput::make('customer.last_name')
                            ->label(__('admin/orders/orders.labels.last_name'))
                            ->required(),
                        TextInput::make('customer.email')
                            ->label(__('admin/orders/orders.labels.email'))
                            ->email()
                            ->nullable(),
                        TextInput::make('customer.telephone')
                            ->label(__('admin/orders/orders.labels.telephone'))
                            ->required(),
                    ])
                    ->columns(),
            ]);
    }

    private static function promoCodeTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.promo_code'))
            ->schema([
                Section::make(__('admin/orders/orders.sections.promo_code'))
                    ->schema([
                        Select::make('promo_code.id')
                            ->label(__('admin/orders/orders.labels.promo_code'))
                            ->getSearchResultsUsing(fn (string $search): array => app(PromoCodeAdminOptionsService::class)
                                ->promoCodeSearchOptions($search))
                            ->getOptionLabelUsing(fn (int|string|null $value): ?string => app(PromoCodeAdminOptionsService::class)
                                ->promoCodeLabelById($value))
                            ->searchable()
                            ->searchDebounce(1000)
                            ->optionsLimit(OrderAdminDeliveryService::SEARCH_LIMIT)
                            ->live()
                            ->nullable(),
                        TextInput::make('promo_code.code')
                            ->label(__('admin/orders/orders.labels.promo_code'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('promo_code.promo_type')
                            ->label(__('admin/orders/orders.labels.promo_type'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('promo_code.discount_type')
                            ->label(__('admin/orders/orders.labels.discount_type'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('promo_code.discount_amount')
                            ->label(__('admin/orders/orders.labels.promo_discount_amount'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix(function (Get $get): string {
                                $discount_type = $get('../discount_type');

                                if ($discount_type instanceof PromoCodeDiscountTypeEnum) {
                                    $discount_type = $discount_type->value;
                                }

                                return $discount_type === PromoCodeDiscountTypeEnum::Percentage->value
                                    ? '%'
                                    : (string) $get('../../currency_code');
                            }),
                        TextInput::make('promo_code.discount_value')
                            ->label(__('admin/orders/orders.labels.promo_discount_value'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix(function (Get $get): string {
                                $discount_type = $get('../discount_type');

                                if ($discount_type instanceof PromoCodeDiscountTypeEnum) {
                                    $discount_type = $discount_type->value;
                                }

                                return $discount_type === PromoCodeDiscountTypeEnum::Percentage->value
                                    ? '%'
                                    : (string) $get('../../currency_code');
                            }),
                        Repeater::make('promo_code.products')
                            ->label(__('admin/orders/orders.labels.promo_products'))
                            ->table([
                                TableColumn::make(__('admin/orders/orders.labels.product')),
                                TableColumn::make(__('admin/orders/orders.labels.identifier'))->width(200),
                                TableColumn::make(__('admin/orders/orders.labels.promo_product_status'))->width(50),
                                TableColumn::make(__('admin/orders/orders.labels.promo_product_is_applying'))->width(50),
                                TableColumn::make(__('admin/orders/orders.labels.force_promo_product')),
                                TableColumn::make(__('admin/orders/orders.labels.quantity'))->width(50),
                                TableColumn::make(__('admin/orders/orders.labels.unit_price'))->width(170),
                                TableColumn::make(__('admin/orders/orders.labels.discount'))->width(170),
                                TableColumn::make(__('admin/orders/orders.labels.line_total'))->width(170),
                            ])
                            ->extraAttributes([
                                'class' => 'promo-code-products-repeater',
                            ], true)
                            ->schema([
                                Hidden::make('id'),
                                Hidden::make('order_product_id'),
                                Hidden::make('is_eligible'),
                                Select::make('product_id')
                                    ->label(__('admin/orders/orders.labels.product'))
                                    ->getSearchResultsUsing(fn (string $search): array => self::searchProducts($search))
                                    ->getOptionLabelUsing(fn (int|string|null $value): ?string => self::getProductLabel($value))
                                    ->searchable()
                                    ->searchDebounce(1000)
                                    ->optionsLimit(OrderAdminDeliveryService::SEARCH_LIMIT)
                                    ->disabled(),
                                TextInput::make('identifier')
                                    ->label(__('admin/orders/orders.labels.identifier'))
                                    ->formatStateUsing(fn (Get $get): string => self::formatProductIdentifier(
                                        (string) $get('model'),
                                        (string) $get('sku'),
                                        (string) $get('ean'),
                                    ))
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('promo_status')
                                    ->label(__('admin/orders/orders.labels.promo_product_status'))
                                    ->formatStateUsing(fn (Get $get): string => self::isPromoProductEnabled($get('is_eligible'))
                                        || self::isPromoProductEnabled($get('force_apply'))
                                        ? __('admin/orders/orders.options.promo_product.active')
                                        : __('admin/orders/orders.options.promo_product.inactive'))
                                    ->disabled()
                                    ->dehydrated(false),
                                IconEntry::make('is_applying')
                                    ->label(__('admin/orders/orders.labels.promo_product_is_applying'))
                                    ->state(fn (Get $get): bool => self::isPromoProductEnabled($get('is_eligible'))
                                        || self::isPromoProductEnabled($get('force_apply')))
                                    ->boolean(),
                                Toggle::make('force_apply')
                                    ->label(__('admin/orders/orders.labels.force_promo_product'))
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        $set('is_applying', self::isPromoProductEnabled($get('is_eligible'))
                                            || self::isPromoProductEnabled($get('force_apply')));
                                    }),
                                Hidden::make('name'),
                                Hidden::make('model'),
                                Hidden::make('sku'),
                                Hidden::make('ean'),
                                TextInput::make('quantity')
                                    ->label(__('admin/orders/orders.labels.quantity'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->disabled(),
                                TextInput::make('unit_price')
                                    ->label(__('admin/orders/orders.labels.unit_price'))
                                    ->numeric()
                                    ->required()
                                    ->suffix(fn (Get $get): string => (string) $get('../../../currency_code'))
                                    ->disabled(),
                                TextInput::make('discount')
                                    ->label(__('admin/orders/orders.labels.discount'))
                                    ->numeric()
                                    ->nullable()
                                    ->suffix(fn (Get $get): string => (string) $get('../../../currency_code'))
                                    ->disabled(),
                                TextInput::make('line_total')
                                    ->label(__('admin/orders/orders.labels.line_total'))
                                    ->numeric()
                                    ->suffix(fn (Get $get): string => (string) $get('../../../currency_code'))
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->defaultItems(0)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(),
            ]);
    }

    private static function shippingTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.shipping'))
            ->schema([
                Section::make(__('admin/orders/orders.sections.shipping'))
                    ->schema([
                        Select::make('shipping.code')
                            ->label(__('admin/orders/orders.labels.shipping_method'))
                            ->options(fn (Get $get): array => app(OrderAdminOptionsService::class)
                                ->getDeliveryMethodOptions((string)$get('shipping.code')))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $set('shipping.method', null);
                                $set('shipping.city', null);
                                $set('shipping.city_id', null);
                                $set('shipping.address', null);
                                $set('shipping.delivery_point', null);
                                $set('shipping.delivery_point_id', null);
                                $set('shipping.postcode', null);
                                $set('shipping.provider_data', null);
                                $set('shipping_cost_enabled', $state !== 'pickup_store');
                            }),
                        Hidden::make('shipping.method'),
                        Select::make('shipping.city_id')
                            ->label(__('admin/orders/orders.labels.city'))
                            ->getSearchResultsUsing(fn (Get $get, string $search): array => app(OrderAdminDeliveryService::class)
                                ->searchCities((string)$get('shipping.code'), $search))
                            ->getOptionLabelUsing(fn (Get $get, int|string|null $value): ?string => self::getCityLabel(
                                (string)$get('shipping.code'),
                                $value,
                            ))
                            ->searchable()
                            ->searchDebounce(1000)
                            ->optionsLimit(OrderAdminDeliveryService::SEARCH_LIMIT)
                            ->live()
                            ->visible(fn (Get $get): bool => app(OrderAdminDeliveryService::class)
                                                                ->getCapabilities((string)$get('shipping.code'))['city'])
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $city_id = (string)$get('shipping.city_id');
                                $city = app(OrderAdminDeliveryService::class)->findCity(
                                    (string)$get('shipping.code'),
                                    $city_id,
                                );
                                $set('shipping.city', $city['name'] ?? null);
                                $set('shipping.delivery_point', null);
                                $set('shipping.delivery_point_id', null);
                                $set('shipping.postcode', null);
                                $set('shipping.provider_data', $city['provider_data'] ?? null);
                            }),
                        TextInput::make('shipping.address')
                            ->label(__('admin/orders/orders.labels.address'))
                            ->visible(fn (Get $get): bool => app(OrderAdminDeliveryService::class)
                                                                ->getCapabilities((string)$get('shipping.code'))['courier_address']),
                        Select::make('shipping.delivery_point_id')
                            ->label(__('admin/orders/orders.labels.delivery_point'))
                            ->getSearchResultsUsing(fn (Get $get, string $search): array => app(OrderAdminDeliveryService::class)
                                ->searchDeliveryPoints(
                                    (string)$get('shipping.code'),
                                    (string)$get('shipping.city_id'),
                                    $search,
                                ))
                            ->getOptionLabelUsing(fn (Get $get, int|string|null $value): ?string => self::getDeliveryPointLabel(
                                (string)$get('shipping.code'),
                                (string)$get('shipping.city_id'),
                                $value,
                            ))
                            ->searchable()
                            ->searchDebounce(1000)
                            ->optionsLimit(OrderAdminDeliveryService::SEARCH_LIMIT)
                            ->live()
                            ->visible(fn (Get $get): bool => app(OrderAdminDeliveryService::class)
                                                                ->getCapabilities((string)$get('shipping.code'))['delivery_point']
                                && filled($get('shipping.city_id')))
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $point = app(OrderAdminDeliveryService::class)->findDeliveryPoint(
                                    (string)$get('shipping.code'),
                                    (string)$get('shipping.city_id'),
                                    (string)$get('shipping.delivery_point_id'),
                                );
                                $set('shipping.delivery_point', $point['name'] ?? null);
                                $set('shipping.postcode', $point['postcode'] ?? null);
                                $set('shipping.provider_data', $point['provider_data'] ?? null);
                            }),
                        TextInput::make('shipping.postcode')
                            ->label(__('admin/orders/orders.labels.postcode'))
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('shipping_cost')
                            ->label(__('admin/orders/orders.labels.shipping_cost'))
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn (Get $get): string => (string)$get('currency_code'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::recalculateTotals(
                                    (array)$get('products'),
                                    $get('shipping_cost'),
                                    (bool)$get('shipping_cost_enabled'),
                                    (array)$get('totals'),
                                    $set,
                                    'totals',
                                );
                            })
                            ->visible(fn (Get $get): bool => app(OrderAdminDeliveryService::class)
                                                                ->getCapabilities((string)$get('shipping.code'))['cost']),
                        Toggle::make('shipping_cost_enabled')
                            ->label(__('admin/orders/orders.labels.shipping_cost_enabled'))
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::recalculateTotals(
                                    (array)$get('products'),
                                    $get('shipping_cost'),
                                    (bool)$get('shipping_cost_enabled'),
                                    (array)$get('totals'),
                                    $set,
                                    'totals',
                                );
                            })
                            ->visible(fn (Get $get): bool => app(OrderAdminDeliveryService::class)
                                                                ->getCapabilities((string)$get('shipping.code'))['cost']),
                        Hidden::make('shipping.delivery_point'),
                        Hidden::make('shipping.city'),
                        KeyValue::make('shipping.provider_data')
                            ->label(__('admin/orders/orders.labels.provider_data'))
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    private static function paymentsTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.payments'))
            ->schema([
                Repeater::make('payments')
                    ->label(__('admin/orders/orders.labels.payments'))
                    ->schema([
                        Hidden::make('id'),
                        Select::make('code')
                            ->label(__('admin/orders/orders.labels.payment_method'))
                            ->options(fn (Get $get): array => app(OrderAdminOptionsService::class)
                                ->getPaymentMethodOptions(app()->getLocale(), (string)$get('code')))
                            ->searchable()
                            ->live(),
                        Hidden::make('method'),
                        Select::make('payment_status_id')
                            ->label(__('admin/orders/orders.labels.payment_status'))
                            ->options(fn (Get $get): array => self::statusOptions(
                                PaymentStatuses::class,
                                is_numeric($get('payment_status_id')) ? (int)$get('payment_status_id') : null,
                            ))
                            ->required(),
                        TextInput::make('transaction_id')
                            ->label(__('admin/orders/orders.labels.transaction_id'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('amount')
                            ->label(__('admin/orders/orders.labels.amount'))
                            ->numeric()
                            ->suffix(fn (Get $get): string => (string)$get('../../currency_code'))
                            ->required(),
                        Textarea::make('failure_reason')
                            ->label(__('admin/orders/orders.labels.failure_reason'))
                            ->disabled()
                            ->dehydrated(false),
                        KeyValue::make('provider_data')
                            ->label(__('admin/orders/orders.labels.provider_data'))
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('paid_at')
                            ->label(__('admin/orders/orders.labels.paid_at'))
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('failed_at')
                            ->label(__('admin/orders/orders.labels.failed_at'))
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns()
                    ->defaultItems(0)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->columnSpanFull(),
            ]);
    }

    private static function productsTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.products'))
            ->schema([
                Repeater::make('products')
                    ->label(__('admin/orders/orders.labels.products'))
                    ->table([
                        TableColumn::make(__('admin/orders/orders.labels.product')),
                        TableColumn::make(__('admin/orders/orders.labels.identifier'))->width(200),
                        TableColumn::make(__('admin/orders/orders.labels.unit_price'))->width(170),
                        TableColumn::make(__('admin/orders/orders.labels.quantity'))->width(120),
                        TableColumn::make(__('admin/orders/orders.labels.discount'))->width(170),
                        TableColumn::make(__('admin/orders/orders.labels.line_total'))->width(170),
                    ])
                    ->schema([
                        Hidden::make('id'),
                        Select::make('product_id')
                            ->label(__('admin/orders/orders.labels.product'))
                            ->getSearchResultsUsing(fn (string $search): array => self::searchProducts($search))
                            ->getOptionLabelUsing(fn (int|string|null $value): ?string => self::getProductLabel($value))
                            ->searchable()
                            ->searchDebounce(1000)
                            ->optionsLimit(OrderAdminDeliveryService::SEARCH_LIMIT)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, int|string|null $state): void {
                                self::fillProductSnapshot($set, $state);
                                self::recalculateLineAndTotals($get, $set);
                            }),
                        Hidden::make('product_variant_id'),
                        Hidden::make('is_default_variant'),
                        Hidden::make('name'),
                        Hidden::make('model'),
                        Hidden::make('sku'),
                        Hidden::make('ean'),
                        TextInput::make('identifier')
                            ->label(__('admin/orders/orders.labels.identifier'))
                            ->formatStateUsing(fn (Get $get): string => self::formatProductIdentifier(
                                (string)$get('model'),
                                (string)$get('sku'),
                                (string)$get('ean'),
                            ))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('unit_price')
                            ->label(__('admin/orders/orders.labels.unit_price'))
                            ->numeric()
                            ->required()
                            ->suffix(fn (Get $get): string => (string)$get('../../currency_code'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::recalculateLineAndTotals($get, $set);
                            }),
                        TextInput::make('quantity')
                            ->label(__('admin/orders/orders.labels.quantity'))
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::recalculateLineAndTotals($get, $set);
                            }),
                        TextInput::make('discount')
                            ->label(__('admin/orders/orders.labels.discount'))
                            ->numeric()
                            ->nullable()
                            ->suffix(fn (Get $get): string => (string)$get('../../currency_code'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::recalculateLineAndTotals($get, $set);
                            }),
                        TextInput::make('line_total')
                            ->label(__('admin/orders/orders.labels.line_total'))
                            ->numeric()
                            ->suffix(fn (Get $get): string => (string)$get('../../currency_code'))
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->defaultItems(0)
                    ->addable()
                    ->deletable()
                    ->reorderable(false)
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        self::recalculateTotals(
                            (array)$get('products'),
                            (array)$get('shipping_cost'),
                            (bool)$get('shipping_cost_enabled'),
                            (array)$get('totals'),
                            $set,
                            'totals',
                        );
                    })
                    ->columnSpanFull(),
            ]);
    }

    private static function totalsTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.totals'))
            ->schema([
                Repeater::make('totals')
                    ->label(__('admin/orders/orders.labels.totals'))
                    ->table([
                        TableColumn::make(__('admin/orders/orders.labels.total_name')),
                        TableColumn::make(__('admin/orders/orders.labels.total_value')),
                    ])
                    ->schema([
                        Hidden::make('id'),
                        Hidden::make('total_type'),
                        TextInput::make('name')
                            ->label(__('admin/orders/orders.labels.total_name'))
                            ->extraAttributes(fn (Get $get): array => ($get('total_type') === 'total')
                                ? ['class' => 'total-row']
                                : [])
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('value')
                            ->label(__('admin/orders/orders.labels.total_value'))
                            ->numeric()
                            ->suffix(fn (Get $get): string => (string)$get('../../currency_code'))
                            ->extraAttributes(fn (Get $get): array => ($get('total_type') === 'total')
                                ? ['class' => 'total-row']
                                : [])
                            ->disabled()
                            ->dehydrated(false),
                        Hidden::make('sort_order'),
                    ])
                    ->columns(3)
                    ->defaultItems(0)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->columnSpanFull(),
            ]);
    }

    private static function historyTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.history'))
            ->schema([
                Repeater::make('histories')
                    ->label(__('admin/orders/orders.labels.histories'))
                    ->schema([
                        TextInput::make('event')
                            ->label(__('admin/orders/orders.labels.event'))
                            ->formatStateUsing(fn (mixed $state): string => app(OrderAdminOptionsService::class)
                                ->getHistoryEventLabel((string)$state))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('comment')
                            ->label(__('admin/orders/orders.labels.comment'))
                            ->disabled()
                            ->dehydrated(false),
                        KeyValue::make('json')
                            ->label(__('admin/orders/orders.labels.history_data'))
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('created_at')
                            ->label(__('admin/default.columns.created_at'))
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns()
                    ->defaultItems(0)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->columnSpanFull(),
            ]);
    }

    private static function currencyTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.currency'))
            ->schema([
                Section::make(__('admin/orders/orders.sections.currency'))
                    ->schema([
                        TextInput::make('currency_code')
                            ->label(__('admin/orders/orders.labels.currency_code'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('exchange_rate')
                            ->label(__('admin/orders/orders.labels.exchange_rate'))
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('currency_id')
                            ->label(__('admin/orders/orders.labels.change_currency'))
                            ->options(fn (): array => self::currencyOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, int|string|null $state): void {
                                self::convertCurrencyFormState($get, $set, $state);
                            }),
                    ])
                    ->columns(),
            ]);
    }

    private static function technicalTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.technical'))
            ->schema([
                Section::make(__('admin/orders/orders.sections.technical'))
                    ->schema([
                        TextInput::make('language_code')
                            ->label(__('admin/orders/orders.labels.language_code'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('ip')
                            ->label(__('admin/orders/orders.labels.ip'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('forwarded_ip')
                            ->label(__('admin/orders/orders.labels.forwarded_ip'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('user_agent')
                            ->label(__('admin/orders/orders.labels.user_agent'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('accept_language')
                            ->label(__('admin/orders/orders.labels.accept_language'))
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('added_at')
                            ->label(__('admin/orders/orders.labels.added_at'))
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(),
            ]);
    }

    /**
     * @param class-string<OrderStatuses|PaymentStatuses> $model
     *
     * @return array<string, string>
     */
    private static function statusOptions(string $model, ?int $selected_status_id = null): array
    {
        $options_service = app(OrderAdminOptionsService::class);

        return $model === OrderStatuses::class
            ? $options_service->getOrderStatusOptions($selected_status_id)
            : $options_service->getPaymentStatusOptions($selected_status_id);
    }

    private static function getCityLabel(string $method, int|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return data_get(
            app(OrderAdminDeliveryService::class)->findCity($method, (string)$value),
            'name',
        );
    }

    private static function getDeliveryPointLabel(
        string          $method,
        string          $city_id,
        int|string|null $value,
    ): ?string {
        if ($value === null || $value === '' || $city_id === '') {
            return null;
        }

        return data_get(
            app(OrderAdminDeliveryService::class)->findDeliveryPoint($method, $city_id, (string)$value),
            'name',
        );
    }

    /**
     * @return array<string, string>
     */
    private static function searchUsers(string $search): array
    {
        $search = Str::trim($search);

        if ($search === '') {
            return [];
        }

        return User::query()
            ->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'like', "%$search%")
                    ->orWhere('lastname', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('telephone', 'like', "%$search%");
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (User $user): array => [(string)$user->getKey() => self::formatUserLabel($user)])
            ->all();
    }

    private static function getUserLabel(int|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $user = User::query()->find($value);

        return $user instanceof User ? self::formatUserLabel($user) : null;
    }

    private static function formatUserLabel(User $user): string
    {
        return sprintf(
            '%s — %s',
            Str::trim($user->name . ' ' . ($user->lastname ?? '')),
            $user->email ?: ($user->telephone ?: (string)$user->getKey()),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function searchProducts(string $search): array
    {
        $search = Str::trim($search);

        if (Str::length($search) < OrderAdminDeliveryService::MIN_SEARCH_LENGTH) {
            return [];
        }

        $language_id = app(OrderAdminOptionsService::class)->getCurrentLanguageId();

        if ($language_id === null) {
            return [];
        }

        return Product::query()
            ->with(['productDescription' => function ($description_query) use ($language_id): void {
                $description_query->where('language_id', $language_id);
            }])
            ->where(function (Builder $query) use ($search, $language_id): void {
                $query
                    ->whereHas('productDescription', fn (Builder $description_query): Builder => $description_query
                        ->where('language_id', $language_id)
                        ->where('name', 'like', "%$search%"))
                    ->orWhere('sku', 'like', "%$search%")
                    ->orWhere('model', 'like', "%$search%")
                    ->orWhere('ean', 'like', "%$search%");
            })
            ->orderByDesc('date_added')
            ->limit(OrderAdminDeliveryService::SEARCH_LIMIT)
            ->get()
            ->mapWithKeys(fn (Product $product): array => [(string)$product->getKey() => self::formatProductLabel($product, $language_id)])
            ->all();
    }

    private static function getProductLabel(int|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $language_id = app(OrderAdminOptionsService::class)->getCurrentLanguageId();

        if ($language_id === null) {
            return null;
        }

        $product = Product::query()
            ->with(['productDescription' => function ($query) use ($language_id): void {
                $query->where('language_id', $language_id);
            }])
            ->find($value);

        return $product instanceof Product ? self::formatProductLabel($product, $language_id) : null;
    }

    private static function formatProductLabel(Product $product, mixed $language_id): string
    {
        $name = $product->productDescription
            ->first(fn ($description): bool => (int)$description->language_id === (int)$language_id)
            ?->name;

        return Str::trim((string)$name);
    }

    /**
     * @param string $model
     * @param string $sku
     * @param string $ean
     *
     * @return string
     */
    private static function formatProductIdentifier(string $model, string $sku, string $ean): string
    {
        return $ean ?: $model ?: $sku;
    }

    private static function isPromoProductEnabled(mixed $state): bool
    {
        return boolean_value($state);
    }

    private static function fillCustomerFromUser(Set $set, int|string|null $state): void
    {
        if ($state === null || $state === '') {
            return;
        }

        $user = User::query()->find($state);

        if (!$user instanceof User) {
            Log::channel('stack')->error('[OrderForm] selected customer user was not found', [
                'user_id' => $state,
            ]);

            return;
        }

        $set('customer.first_name', $user->name);
        $set('customer.last_name', $user->lastname);
        $set('customer.email', $user->email);
        $set('customer.telephone', $user->telephone);
        $set('customer.user_group_id', $user->user_group_id);
    }

    private static function fillProductSnapshot(Set $set, int|string|null $state): void
    {
        if ($state === null || $state === '') {
            return;
        }

        $language_id = app(OrderAdminOptionsService::class)->getCurrentLanguageId();

        if ($language_id === null) {
            return;
        }

        $product = Product::query()
            ->with([
                'defaultVariant',
                'productDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->find($state);

        if (!$product instanceof Product) {
            Log::channel('stack')->error('[OrderForm] selected order product was not found', [
                'product_id' => $state,
            ]);

            return;
        }

        $description = $product->productDescription->first();
        $variant = $product->defaultVariant;
        $variant_id = null;
        $is_default_variant = false;
        $unit_price = (float)($product->price ?? 0);

        if ($variant instanceof ProductVariant) {
            $variant_id = $variant->getKey();
            $is_default_variant = $variant->is_default;
            $unit_price = $variant->price;
        }

        $set('product_variant_id', $variant_id);
        $set('is_default_variant', $is_default_variant);
        $set('name', $description?->name ?: $product->model ?: $product->sku ?: (string)$product->getKey());
        $set('model', $product->model);
        $set('sku', $product->sku);
        $set('ean', $product->ean);
        $set('unit_price', $unit_price);
    }

    private static function recalculateLineAndTotals(Get $get, Set $set): void
    {
        $quantity = max(1, (int)$get('quantity'));
        $unit_price = max(0, (float)$get('unit_price'));
        $discount = max(0, (float)($get('discount') ?: 0));
        $line_total = ((float) $quantity * (float) $unit_price) - (float) $discount;

        $set(
            'line_total',
            max(0.0, round($line_total, 4)),
        );

        self::recalculateTotals(
            (array)$get('../../products'),
            $get('../../shipping_cost'),
            (bool)$get('../../shipping_cost_enabled'),
            (array)$get('../../totals'),
            $set,
            '../../totals',
        );
    }

    /**
     * @return array<string, string>
     */
    private static function currencyOptions(): array
    {
        return app(OrderAdminOptionsService::class)->getCurrencyOptions();
    }

    private static function convertCurrencyFormState(Get $get, Set $set, int|string|null $state): void
    {
        if ($state === null || $state === '') {
            return;
        }

        $target_currency = Currency::query()
            ->whereKey((int)$state)
            ->where('is_active', true)
            ->first();

        if (!$target_currency instanceof Currency) {
            Log::channel('stack')->error('[OrderForm] selected order currency was not found', [
                'currency_id' => $state,
            ]);

            return;
        }

        $source_code = (string)$get('currency_code');
        $target_code = (string)$target_currency->getAttribute('code');

        if ($source_code === $target_code) {
            return;
        }

        try {
            $convert_price = app(ConvertPrice::class);
            $source_exchange_rate = (float)$get('exchange_rate');

            if ($source_exchange_rate <= 0 && $source_code !== '') {
                $source_exchange_rate = (float)Currency::query()
                    ->where('code', $source_code)
                    ->where('is_active', true)
                    ->value('exchange_rate');
            }

            $target_exchange_rate = (float)$target_currency->getAttribute('exchange_rate');
            $target_decimal_places = (int)$target_currency->getAttribute('decimal_places');

            if ($source_exchange_rate <= 0 || $target_exchange_rate <= 0) {
                Log::channel('stack')->error('[OrderForm] order currency conversion skipped because exchange rate is invalid', [
                    'source_currency' => $source_code,
                    'source_exchange_rate' => $source_exchange_rate,
                    'target_currency' => $target_code,
                    'target_exchange_rate' => $target_exchange_rate,
                ]);

                return;
            }

            $products = (array)$get('products');

            foreach ($products as $index => $product) {
                if (!is_array($product)) {
                    continue;
                }

                foreach (['unit_price', 'discount', 'line_total'] as $field) {
                    $set(
                        "products.$index.$field",
                        round($convert_price->convertUsingExchangeRates(
                            (float)($product[$field] ?? 0),
                            $source_exchange_rate,
                            $target_exchange_rate,
                            $target_decimal_places,
                        ), 4),
                    );
                }
            }

            $totals = (array)$get('totals');

            foreach ($totals as $index => $total) {
                if (!is_array($total)) {
                    continue;
                }

                $set(
                    "totals.$index.value",
                    round($convert_price->convertUsingExchangeRates(
                        (float)($total['value'] ?? 0),
                        $source_exchange_rate,
                        $target_exchange_rate,
                        $target_decimal_places,
                    ), 4),
                );
            }

            $payments = (array)$get('payments');

            foreach ($payments as $index => $payment) {
                if (!is_array($payment)) {
                    continue;
                }

                $set(
                    "payments.$index.amount",
                    round($convert_price->convertUsingExchangeRates(
                        (float)($payment['amount'] ?? 0),
                        $source_exchange_rate,
                        $target_exchange_rate,
                        $target_decimal_places,
                    ), 4),
                );
            }

            $set('shipping_cost', round($convert_price->convertUsingExchangeRates(
                (float)$get('shipping_cost'),
                $source_exchange_rate,
                $target_exchange_rate,
                $target_decimal_places,
            ), 4));
            $set('currency_code', $target_code);
            $set('exchange_rate', $target_exchange_rate);
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[OrderForm] order currency conversion failed', [
                'target_currency' => $target_code,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * @param array<int, mixed> $products
     * @param array<string, mixed> $shipping_cost
     * @param array<int, mixed> $totals
     */
    private static function recalculateTotals(
        array                       $products,
        array|float|int|string|null $shipping_cost,
        bool                        $shipping_cost_enabled,
        array                       $totals,
        Set                         $set,
        string                      $totals_path,
    ): void {
        $subtotal = collect($products)
            ->filter(fn (mixed $value): bool => is_array($value))
            ->sum(function (array $product): float {
                $quantity = max(1, (int)($product['quantity'] ?? 1));
                $unit_price = max(0, (float)($product['unit_price'] ?? 0));
                $discount = max(0, (float)($product['discount'] ?? 0));
                $line_total = ((float) $quantity * (float) $unit_price) - (float) $discount;

                return max(0.0, round($line_total, 4));
            });
        $shipping_value = ! $shipping_cost_enabled || is_array($shipping_cost)
            ? 0.0
            : max(0, (float)$shipping_cost);
        $grand_total = 0.0;

        $updated_totals = collect($totals)
            ->filter(fn (mixed $value): bool => is_array($value))
            ->map(function (array $total) use ($subtotal, $shipping_value, &$grand_total): array {
                $total_type = (string)($total['total_type'] ?? '');
                $value = (float)($total['value'] ?? 0);

                if ($total_type === 'sub_total') {
                    $value = $subtotal;
                } elseif ($total_type === 'shipping') {
                    $value = $shipping_value;
                }

                if ($total_type !== 'total') {
                    $grand_total = $grand_total + (float) $value;
                }

                $total['value'] = round($value, 4);

                return $total;
            })
            ->map(function (array $total) use ($grand_total): array {
                if (($total['total_type'] ?? '') === 'total') {
                    $total['value'] = round($grand_total, 4);
                }

                return $total;
            })
            ->values()
            ->all();

        $set($totals_path, $updated_totals);
    }
}
