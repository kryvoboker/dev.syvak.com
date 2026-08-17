<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use App\Models\ApplicationSettings\Language;
use App\Models\Orders\Orders;
use App\Models\Orders\OrderStatuses;
use App\Models\Payment\PaymentStatuses;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        $language_id = app(Language::class)->getLanguageByCode(app()->getLocale())->id;

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($language_id): void {
                $query
                    ->with([
                        'customer' => function ($customer_query): void {
                            $customer_query->select(['id', 'order_id', 'first_name', 'last_name']);
                        },
                        'shipping' => function ($shipping_query): void {
                            $shipping_query->select(['id', 'order_id', 'method']);
                        },
                        'products' => function ($product_query): void {
                            $product_query->select(['id', 'order_id', 'name', 'ean']);
                        },
                        'status' => function ($status_query): void {
                            $status_query->select(['id', 'code']);
                        },
                        'status.descriptions' => function ($description_query) use ($language_id): void {
                            $description_query
                                ->select(['id', 'order_status_id', 'language_id', 'name'])
                                ->where('language_id', $language_id);
                        },
                        'payments' => function ($payment_query): void {
                            $payment_query->select(['id', 'order_id', 'payment_status_id']);
                        },
                        'payments.paymentStatus' => function ($status_query): void {
                            $status_query->select(['id', 'code']);
                        },
                        'payments.paymentStatus.descriptions' => function ($description_query) use ($language_id): void {
                            $description_query
                                ->select(['id', 'payment_status_id', 'language_id', 'name'])
                                ->where('language_id', $language_id);
                        },
                    ]);
            })
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin/orders/orders.columns.id'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order_number')
                    ->label(__('admin/orders/orders.columns.order_number'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('customer.first_name')
                    ->label(__('admin/orders/orders.columns.customer'))
                    ->state(fn (Orders $record): string => Str::trim($record->customer?->first_name . ' ' . $record->customer?->last_name))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'customer',
                        fn (Builder $customer_query): Builder => $customer_query
                            ->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%"),
                    ))
                    ->sortable(),

                TextColumn::make('products.name')
                    ->label(__('admin/orders/orders.columns.products'))
                    ->state(fn (Orders $record): string => $record->products->pluck('name')->filter()->implode(', '))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'products',
                        fn (Builder $product_query): Builder => $product_query->where('name', 'like', "%$search%"),
                    ))
                    ->limit(80),

                TextColumn::make('products.ean')
                    ->label(__('admin/default.columns.ean'))
                    ->state(fn (Orders $record): string => $record->products->pluck('ean')->filter()->unique()->implode(', '))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'products',
                        fn (Builder $product_query): Builder => $product_query->where('ean', 'like', "%$search%"),
                    ))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total')
                    ->label(__('admin/orders/orders.columns.total'))
                    ->formatStateUsing(fn (Orders $record): string => sprintf('%s %s', $record->total, $record->currency_code))
                    ->sortable(),

                TextColumn::make('status.code')
                    ->label(__('admin/orders/orders.columns.order_status'))
                    ->state(function (Orders $record) use ($language_id): string {
                        return self::getStatusName(
                            $record->status,
                            $language_id,
                        );
                    })
                    ->badge(),

                TextColumn::make('payments.paymentStatus.code')
                    ->label(__('admin/orders/orders.columns.payment_status'))
                    ->state(function (Orders $record) use ($language_id): string {
                        return $record->payments
                            ->map(fn ($payment): string => self::getStatusName($payment->paymentStatus, $language_id))
                            ->filter()
                            ->unique()
                            ->implode(', ');
                    })
                    ->badge(),

                TextColumn::make('shipping.method')
                    ->label(__('admin/orders/orders.columns.shipping_method'))
                    ->toggleable(),

                TextColumn::make('added_at')
                    ->label(__('admin/orders/orders.columns.added_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable(),

                TextColumn::make('deleted_at')
                    ->label(__('admin/orders/orders.columns.deleted_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->placeholder(__('admin/orders/orders.placeholders.not_deleted'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('order_number_filter')
                    ->label(__('admin/orders/orders.filters.order_number'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('admin/orders/orders.filters.order_number')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::whereLike($query, 'order_number', $data['value'] ?? null)),

                Filter::make('customer_first_name')
                    ->label(__('admin/orders/orders.filters.customer_first_name'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('admin/orders/orders.filters.customer_first_name')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::whereCustomerLike($query, 'first_name', $data['value'] ?? null)),

                Filter::make('customer_last_name')
                    ->label(__('admin/orders/orders.filters.customer_last_name'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('admin/orders/orders.filters.customer_last_name')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::whereCustomerLike($query, 'last_name', $data['value'] ?? null)),

                Filter::make('product_name')
                    ->label(__('admin/orders/orders.filters.product_name'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('admin/orders/orders.filters.product_name')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::whereProductLike($query, 'name', $data['value'] ?? null)),

                Filter::make('product_ean')
                    ->label(__('admin/orders/orders.filters.ean'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('admin/orders/orders.filters.ean')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::whereProductLike($query, 'ean', $data['value'] ?? null)),

                Filter::make('date_range')
                    ->label(__('admin/orders/orders.filters.date'))
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('admin/orders/orders.filters.date_from')),
                        DatePicker::make('until')
                            ->label(__('admin/orders/orders.filters.date_until')),
                    ])
                    ->columns()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $date_query, string $from): Builder => $date_query->whereDate('added_at', '>=', $from))
                            ->when($data['until'] ?? null, fn (Builder $date_query, string $until): Builder => $date_query->whereDate('added_at', '<=', $until));
                    }),

                SelectFilter::make('order_status_id')
                    ->label(__('admin/orders/orders.filters.order_status'))
                    ->options(fn (): array => self::statusOptions(new OrderStatuses(), $language_id)),

                SelectFilter::make('payment_status_id')
                    ->label(__('admin/orders/orders.filters.payment_status'))
                    ->options(fn (): array => self::statusOptions(new PaymentStatuses(), $language_id))
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $payment_query, string $status_id): Builder => $payment_query->whereHas(
                            'payments',
                            fn (Builder $query_2): Builder => $query_2->where('payment_status_id', $status_id),
                        ),
                    )),

                TrashedFilter::make()
                    ->label(__('admin/orders/orders.filters.deletion_status'))
                    ->placeholder(__('admin/orders/orders.filters.not_deleted'))
                    ->trueLabel(__('admin/orders/orders.filters.all'))
                    ->falseLabel(__('admin/orders/orders.filters.deleted_only')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records): void {
                            self::logBulkMutation('soft_delete', $records);
                        }),
                    RestoreBulkAction::make()
                        ->before(function (Collection $records): void {
                            self::logBulkMutation('restore', $records);
                        }),
                    ForceDeleteBulkAction::make()
                        ->before(function (Collection $records): void {
                            self::logBulkMutation('force_delete', $records);
                        }),
                ]),
            ])
            ->defaultSort('added_at', 'desc');
    }

    /**
     * @param Builder<\App\Models\Orders\Orders> $query
     * @return Builder<\App\Models\Orders\Orders>
     */
    private static function whereLike(Builder $query, string $column, mixed $value): Builder
    {
        $value = trim((string) $value);

        return $value === '' ? $query : $query->where($column, 'like', "%$value%");
    }

    /**
     * @param Builder<\App\Models\Orders\Orders> $query
     * @return Builder<\App\Models\Orders\Orders>
     */
    private static function whereCustomerLike(Builder $query, string $column, mixed $value): Builder
    {
        $value = trim((string) $value);

        return $value === '' ? $query : $query->whereHas(
            'customer',
            fn (Builder $customer_query): Builder => $customer_query->where($column, 'like', "%$value%"),
        );
    }

    /**
     * @param Builder<\App\Models\Orders\Orders> $query
     * @return Builder<\App\Models\Orders\Orders>
     */
    private static function whereProductLike(Builder $query, string $column, mixed $value): Builder
    {
        $value = trim((string) $value);

        return $value === '' ? $query : $query->whereHas(
            'products',
            fn (Builder $product_query): Builder => $product_query->where($column, 'like', "%$value%"),
        );
    }

    /**
     * @param OrderStatuses|PaymentStatuses $model
     * @param mixed                         $language_id
     *
     * @return array<string, string>
     */
    private static function statusOptions(OrderStatuses|PaymentStatuses $model, mixed $language_id): array
    {
        return $model::query()
            ->with(['descriptions' => function ($description_query) use ($language_id): void {
                $description_query->where('language_id', $language_id);
            }])
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(function (OrderStatuses|PaymentStatuses $status) use ($language_id): array {
                $description = $status->descriptions
                    ->first(fn ($item): bool => (int) $item->language_id === (int) $language_id);

                return [(string) $status->getKey() => (string) ($description?->name ?: $status->code)];
            })
            ->all();
    }

    private static function getStatusName(OrderStatuses|PaymentStatuses|null $status, mixed $language_id): string
    {
        if (! $status instanceof OrderStatuses && ! $status instanceof PaymentStatuses) {
            return '';
        }

        $description = $status->descriptions
            ->first(fn ($item): bool => (int) $item->language_id === (int) $language_id);

        return (string) ($description?->name ?: $status->code);
    }

    /**
     * @param  Collection<int, Orders>  $records
     */
    private static function logBulkMutation(string $action, Collection $records): void
    {
        Log::channel('daily')->info('[OrdersTable] bulk order mutation requested', [
            'action' => $action,
            'admin_user_id' => auth()->id(),
            'order_ids' => $records->modelKeys(),
            'order_numbers' => $records->pluck('order_number')->values()->all(),
            'count' => $records->count(),
        ]);
    }
}
