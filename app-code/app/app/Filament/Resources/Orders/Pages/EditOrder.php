<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\ApplicationSettings\Currency;
use App\Models\Orders\Orders;
use App\Services\Order\OrderAdminDeliveryService;
use App\Services\Order\OrderAdminOptionsService;
use App\Services\Order\OrderAdminPersistenceService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return __('admin/orders/orders.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/orders/orders.navigation_label');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (!$record instanceof Orders) {
            return $data;
        }

        $language_id = app(OrderAdminOptionsService::class)->getCurrentLanguageId();
        $record->loadMissing([
            'customer',
            'shipping',
            'payments',
            'products',
            'totals',
            'histories' => function ($query): void {
                $query
                    ->orderByDesc('created_at')
                    ->orderByDesc('id');
            },
            'status.descriptions' => function ($query) use ($language_id): void {
                $query
                    ->select(['id', 'order_status_id', 'language_id', 'name'])
                    ->when($language_id !== null, function ($description_query) use ($language_id): void {
                        $description_query->where('language_id', $language_id);
                    });
            },
        ]);

        $data['id'] = $record->getKey();
        $data['currency_id'] = $record->currency_id;
        $data['currency_code'] = $record->currency_code;
        $exchange_rate = (float)$record->exchange_rate;

        if ($exchange_rate <= 0 && $record->currency_id !== null) {
            $exchange_rate = (float)Currency::query()
                ->whereKey($record->currency_id)
                ->value('exchange_rate');
        }

        $data['exchange_rate'] = $exchange_rate;
        $data['order_status_name'] = app(OrderAdminOptionsService::class)->getStatusLabel($record->status);
        $data['customer'] = $record->customer?->toArray() ?? [];
        $data['shipping'] = $record->shipping?->toArray() ?? [];
        $data['payments'] = $record->payments->toArray();
        $data['products'] = self::prepareProducts($record->products->toArray());
        $shipping_cost = $record->totals->firstWhere('total_type', 'shipping')?->value;
        $data['shipping_cost'] = $shipping_cost ?? app(OrderAdminDeliveryService::class)
            ->getDefaultDeliveryCost($data['shipping']['code'] ?? null);
        $data['shipping_cost_enabled'] = ($data['shipping']['code'] ?? null) !== 'pickup_store'
            && ($data['shipping']['is_cost_enabled'] ?? ((float)$data['shipping_cost'] > 0));
        $shipping_code = (string)($data['shipping']['code'] ?? '');
        $shipping_has_cost = app(OrderAdminDeliveryService::class)
                                             ->getCapabilities($shipping_code)['cost'];
        $data['totals'] = self::prepareTotals(
            $record->totals->toArray(),
            $data['products'],
            $data['shipping_cost'],
            $data['shipping_cost_enabled'],
            $shipping_has_cost,
        );
        $data['histories'] = $record->histories->toArray();

        return $data;
    }

    /**
     * @param array<int, array<string, mixed>> $products
     *
     * @return array<int, array<string, mixed>>
     */
    private static function prepareProducts(array $products): array
    {
        return collect($products)
            ->map(function (array $product): array {
                $quantity = max(1, (int)($product['quantity'] ?? 1));
                $unit_price = max(0, (float)($product['unit_price'] ?? 0));
                $discount = max(0, (float)($product['discount'] ?? 0));
                $line_total = ((float) $quantity * (float) $unit_price) - (float) $discount;
                $product['line_total'] = max(0.0, round((float) $line_total, 4));

                return $product;
            })
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $totals
     * @param array<int, array<string, mixed>> $products
     *
     * @return array<int, array<string, mixed>>
     */
    private static function prepareTotals(
        array $totals,
        array $products,
        mixed $shipping_cost,
        bool  $shipping_cost_enabled,
        bool  $shipping_has_cost,
    ): array {
        $total_types = collect($totals)->pluck('total_type')->map(static fn (mixed $type): string => (string)$type);
        $next_sort_order = collect($totals)->max(
            static fn (array $total): int => (int)($total['sort_order'] ?? 0),
        ) + 1;

        if ($shipping_has_cost && !$total_types->contains('shipping')) {
            $totals[] = [
                'id' => null,
                'total_type' => 'shipping',
                'name' => __('admin/orders/orders.labels.shipping_cost'),
                'value' => is_array($shipping_cost) ? 0.0 : (float)$shipping_cost,
                'sort_order' => $next_sort_order++,
            ];
        }

        if (!$total_types->contains('total')) {
            $totals[] = [
                'id' => null,
                'total_type' => 'total',
                'name' => __('admin/orders/orders.labels.order_total'),
                'value' => 0.0,
                'sort_order' => $next_sort_order,
            ];
        }

        $subtotal = collect($products)->sum(fn (array $product): float => (float)($product['line_total'] ?? 0));
        $shipping_value = !$shipping_cost_enabled || is_array($shipping_cost)
            ? 0.0
            : max(0, (float)$shipping_cost);
        $grand_total = 0.0;

        $prepared_totals = collect($totals)
            ->map(function (array $total) use ($subtotal, $shipping_value, &$grand_total): array {
                $total_type = (string)($total['total_type'] ?? '');
                $value = (float)($total['value'] ?? 0);

                if ($total_type === 'sub_total') {
                    $value = $subtotal;
                } elseif ($total_type === 'shipping') {
                    $value = $shipping_value;
                }

                if ($total_type !== 'total') {
                    $grand_total = (float) $grand_total + (float) $value;
                }

                $total['value'] = round($value, 4);

                return $total;
            });

        return $prepared_totals
            ->map(function (array $total) use ($grand_total): array {
                if (($total['total_type'] ?? '') === 'total') {
                    $total['value'] = round($grand_total, 4);
                }

                return $total;
            })
            ->all();
    }

    /**
     * @param Model $record
     * @param array $data
     *
     * @throws Halt
     * @return Model
     */
    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (!$record instanceof Orders) {
            throw new LogicException('Order record has invalid type.');
        }

        try {
            return app(OrderAdminPersistenceService::class)->update($record, $data);
        } catch (Throwable $throwable) {
            report($throwable);

            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/orders/orders.errors.update_failed'))
                ->danger()
                ->send();

            $this->halt();
            throw $throwable;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->after(function (Orders $record): void {
                    self::logMutation('soft_delete', $record);
                }),
            RestoreAction::make()
                ->icon(Heroicon::ArrowPath)
                ->after(function (Orders $record): void {
                    self::logMutation('restore', $record);
                }),
            ForceDeleteAction::make()
                ->icon(Heroicon::Trash)
                ->after(function (Orders $record): void {
                    self::logMutation('force_delete', $record);
                }),
        ];
    }

    private static function logMutation(string $action, Orders $record): void
    {
        Log::channel('daily')->info('[EditOrder] order mutation completed', [
            'action' => $action,
            'admin_user_id' => auth()->id(),
            'order_id' => $record->getKey(),
            'order_number' => $record->order_number,
        ]);
    }
}
