<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Orders\OrderStatuses;
use App\Models\Payment\PaymentStatuses;
use App\Models\Users\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('OrderTabs')
                    ->tabs([
                        self::generalTab(),
                        self::customerTab(),
                        self::shippingTab(),
                        self::paymentsTab(),
                        self::productsTab(),
                        self::totalsTab(),
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
                        Select::make('order_status_id')
                            ->label(__('admin/orders/orders.labels.order_status'))
                            ->options(fn (): array => self::statusOptions(OrderStatuses::class))
                            ->required(),
                        TextInput::make('order_status_name')
                            ->label(__('admin/orders/orders.labels.order_status_name'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('order_type')
                            ->label(__('admin/orders/orders.labels.order_type'))
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('comment')
                            ->label(__('admin/orders/orders.labels.comment'))
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('total')
                            ->label(__('admin/orders/orders.labels.total'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
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
                    ->columns(2),
            ]);
    }

    private static function shippingTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.shipping'))
            ->schema([
                Section::make(__('admin/orders/orders.sections.shipping'))
                    ->schema([
                        TextInput::make('shipping.method')
                            ->label(__('admin/orders/orders.labels.shipping_method')),
                        TextInput::make('shipping.code')
                            ->label(__('admin/orders/orders.labels.shipping_code')),
                        TextInput::make('shipping.city')
                            ->label(__('admin/orders/orders.labels.city')),
                        TextInput::make('shipping.city_id')
                            ->label(__('admin/orders/orders.labels.city_id')),
                        TextInput::make('shipping.address')
                            ->label(__('admin/orders/orders.labels.address')),
                        TextInput::make('shipping.delivery_point')
                            ->label(__('admin/orders/orders.labels.delivery_point')),
                        TextInput::make('shipping.delivery_point_id')
                            ->label(__('admin/orders/orders.labels.delivery_point_id')),
                        TextInput::make('shipping.postcode')
                            ->label(__('admin/orders/orders.labels.postcode')),
                        KeyValue::make('shipping.provider_data')
                            ->label(__('admin/orders/orders.labels.provider_data'))
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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
                        TextInput::make('method')
                            ->label(__('admin/orders/orders.labels.payment_method')),
                        TextInput::make('code')
                            ->label(__('admin/orders/orders.labels.payment_code')),
                        Select::make('payment_status_id')
                            ->label(__('admin/orders/orders.labels.payment_status'))
                            ->options(fn (): array => self::statusOptions(PaymentStatuses::class))
                            ->required(),
                        TextInput::make('transaction_id')
                            ->label(__('admin/orders/orders.labels.transaction_id')),
                        TextInput::make('amount')
                            ->label(__('admin/orders/orders.labels.amount'))
                            ->numeric()
                            ->required(),
                        Textarea::make('failure_reason')
                            ->label(__('admin/orders/orders.labels.failure_reason')),
                        KeyValue::make('provider_data')
                            ->label(__('admin/orders/orders.labels.provider_data'))
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('paid_at')
                            ->label(__('admin/orders/orders.labels.paid_at')),
                        DateTimePicker::make('failed_at')
                            ->label(__('admin/orders/orders.labels.failed_at')),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addable()
                    ->deletable()
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
                    ->schema([
                        Hidden::make('id'),
                        Select::make('product_id')
                            ->label(__('admin/orders/orders.labels.product'))
                            ->getSearchResultsUsing(fn (string $search): array => self::searchProducts($search))
                            ->getOptionLabelUsing(fn (int|string|null $value): ?string => self::getProductLabel($value))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, int|string|null $state): void {
                                self::fillProductSnapshot($set, $state);
                            }),
                        Hidden::make('product_variant_id'),
                        Hidden::make('is_default_variant'),
                        Hidden::make('name'),
                        Hidden::make('model'),
                        Hidden::make('sku'),
                        Hidden::make('ean'),
                        TextInput::make('quantity')
                            ->label(__('admin/orders/orders.labels.quantity'))
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::recalculateLine($get, $set);
                            }),
                        TextInput::make('discount')
                            ->label(__('admin/orders/orders.labels.discount'))
                            ->numeric()
                            ->nullable()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::recalculateLine($get, $set);
                            }),
                        TextInput::make('unit_price')
                            ->label(__('admin/orders/orders.labels.unit_price'))
                            ->numeric()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                self::recalculateLine($get, $set);
                            }),
                        TextInput::make('line_total')
                            ->label(__('admin/orders/orders.labels.line_total'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->columnSpanFull(),
            ]);
    }

    private static function totalsTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/orders/orders.tabs.totals'))
            ->schema([
                Repeater::make('totals')
                    ->label(__('admin/orders/orders.labels.totals'))
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('total_type')
                            ->label(__('admin/orders/orders.labels.total_type'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('name')
                            ->label(__('admin/orders/orders.labels.total_name'))
                            ->required(),
                        TextInput::make('value')
                            ->label(__('admin/orders/orders.labels.total_value'))
                            ->numeric()
                            ->required(),
                        TextInput::make('sort_order')
                            ->label(__('admin/orders/orders.labels.sort_order'))
                            ->numeric()
                            ->required(),
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
                    ->columns(2)
                    ->defaultItems(0)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->columnSpanFull(),
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
                        TextInput::make('currency_code')
                            ->label(__('admin/orders/orders.labels.currency_code'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('exchange_rate')
                            ->label(__('admin/orders/orders.labels.exchange_rate'))
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
                    ->columns(2),
            ]);
    }

    /**
     * @param class-string<OrderStatuses|PaymentStatuses> $model
     * @return array<string, string>
     */
    private static function statusOptions(string $model): array
    {
        return $model::query()
            ->orderBy('sort_order')
            ->pluck('code', 'id')
            ->map(static fn (mixed $code): string => (string) $code)
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function searchUsers(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        return User::query()
            ->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (User $user): array => [(string) $user->getKey() => self::formatUserLabel($user)])
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
            trim($user->name . ' ' . ($user->lastname ?? '')),
            $user->email ?: ($user->telephone ?: (string) $user->getKey()),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function searchProducts(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $language_id = Language::query()
            ->where('code', app()->getLocale())
            ->value('id');

        return Product::query()
            ->with([
                'productDescription' => fn ($query) => $query->where('language_id', $language_id),
            ])
            ->where(function (Builder $query) use ($search, $language_id): void {
                $query
                    ->whereHas('productDescription', fn (Builder $description_query): Builder => $description_query
                        ->where('language_id', $language_id)
                        ->where('name', 'like', "%{$search}%"))
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('ean', 'like', "%{$search}%");
            })
            ->orderByDesc('date_added')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Product $product): array => [(string) $product->getKey() => self::formatProductLabel($product, $language_id)])
            ->all();
    }

    private static function getProductLabel(int|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $language_id = Language::query()
            ->where('code', app()->getLocale())
            ->value('id');
        $product = Product::query()
            ->with(['productDescription' => fn ($query) => $query->where('language_id', $language_id)])
            ->find($value);

        return $product instanceof Product ? self::formatProductLabel($product, $language_id) : null;
    }

    private static function formatProductLabel(Product $product, mixed $language_id): string
    {
        $name = $product->productDescription
            ->first(fn ($description): bool => (int) $description->language_id === (int) $language_id)
            ?->name;
        $identifiers = collect([$product->sku, $product->model, $product->ean])
            ->filter()
            ->implode(' / ');

        return trim(implode(' — ', array_filter([(string) $name, $identifiers, '#' . $product->getKey()])));
    }

    private static function fillCustomerFromUser(Set $set, int|string|null $state): void
    {
        if ($state === null || $state === '') {
            return;
        }

        $user = User::query()->find($state);

        if (! $user instanceof User) {
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

        $product = Product::query()
            ->with(['defaultVariant', 'productDescription' => fn ($query) => $query->where('language_id', Language::query()->where('code', app()->getLocale())->value('id'))])
            ->find($state);

        if (! $product instanceof Product) {
            Log::channel('stack')->error('[OrderForm] selected order product was not found', [
                'product_id' => $state,
            ]);

            return;
        }

        $description = $product->productDescription->first();
        $variant = $product->defaultVariant;
        $variant_id = null;
        $is_default_variant = false;
        $unit_price = (float) ($product->price ?? 0);

        if ($variant instanceof ProductVariant) {
            $variant_id = $variant->getKey();
            $is_default_variant = $variant->is_default;
            $unit_price = (float) $variant->price;
        }

        $set('product_variant_id', $variant_id);
        $set('is_default_variant', $is_default_variant);
        $set('name', $description?->name ?: $product->model ?: $product->sku ?: (string) $product->getKey());
        $set('model', $product->model);
        $set('sku', $product->sku);
        $set('ean', $product->ean);
        $set('unit_price', $unit_price);
    }

    private static function recalculateLine(Get $get, Set $set): void
    {
        $quantity = max(1, (int) $get('quantity'));
        $unit_price = max(0, (float) $get('unit_price'));
        $discount = max(0, (float) ($get('discount') ?: 0));

        $set('line_total', max(0, round($quantity * $unit_price - $discount, 4)));
    }
}
