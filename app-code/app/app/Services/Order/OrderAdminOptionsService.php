<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Order\DeliveryMethodEnum;
use App\Models\ApplicationSettings\Currency;
use App\Models\Orders\OrderStatuses;
use App\Models\Payment\PaymentStatuses;
use App\Models\Users\UserGroup;
use App\Supports\Services\RequestLookupContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Modules\BankTransfer\Services\Storefront\BankTransferModuleDataService;
use Modules\BankTransfer\Support\BankTransferConfig;
use Modules\PaymentUponDelivery\Services\Storefront\PaymentUponDeliveryModuleDataService;
use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;
use Modules\WayForPay\Services\Storefront\WayForPayModuleDataService;
use Modules\WayForPay\Support\WayForPayConfig;

final class OrderAdminOptionsService
{
    /** @var array<string, string>|null */
    private ?array $delivery_method_options = null;

    /** @var array<string, array<string, string>> */
    private array $payment_method_options = [];

    /** @var array<string, array<string, string>> */
    private array $status_options = [];

    /** @var array<string, string>|null */
    private ?array $currency_options = null;

    /** @var array<string, string>|null */
    private ?array $user_group_options = null;

    private bool $language_id_resolved = false;

    private ?int $language_id = null;

    public function __construct(
        private readonly BankTransferModuleDataService $bank_transfer_module_data_service,
        private readonly PaymentUponDeliveryModuleDataService $payment_upon_delivery_module_data_service,
        private readonly WayForPayModuleDataService $wayforpay_module_data_service,
        private readonly WayForPayConfig $wayforpay_config,
        private readonly RequestLookupContext $request_lookup_context,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getOrderStatusOptions(?int $selected_status_id = null): array
    {
        return $this->getStatusOptions(OrderStatuses::class, $selected_status_id);
    }

    /**
     * @return array<string, string>
     */
    public function getPaymentStatusOptions(?int $selected_status_id = null): array
    {
        return $this->getStatusOptions(PaymentStatuses::class, $selected_status_id);
    }

    public function getStatusLabel(OrderStatuses|PaymentStatuses|null $status): string
    {
        if ($status === null) {
            return $this->translate('admin/orders/orders.statuses.unnamed', 'Unnamed status');
        }

        $name = $status->relationLoaded('descriptions')
            ? $status->descriptions->first()?->name
            : $status->descriptions()
                ->whereHas('language', fn (Builder $language_query): Builder => $language_query
                    ->where('code', app()->getLocale()))
                ->value('name');

        return $name
            ?: $this->translate('admin/orders/orders.statuses.unnamed', 'Unnamed status');
    }

    /**
     * @return array<string, string>
     */
    public function getOrderTypeOptions(): array
    {
        return collect(CartModeEnum::cases())
            ->mapWithKeys(fn (CartModeEnum $mode): array => [
                $mode->value => $this->translate(
                    "admin/orders/orders.order_types.{$mode->value}",
                    $mode->value,
                ),
            ])
            ->all();
    }

    public function getOrderTypeLabel(?string $order_type): string
    {
        return $this->getOrderTypeOptions()[$order_type ?? '']
            ?? $this->translate('admin/orders/orders.order_types.unknown', 'Unknown order type');
    }

    public function getHistoryEventLabel(?string $event): string
    {
        if ($event === null || Str::trim($event) === '') {
            return $this->translate('admin/orders/orders.history_events.unknown', 'Unknown event');
        }

        return $this->translate(
            "admin/orders/orders.history_events.{$event}",
            $this->translate('admin/orders/orders.history_events.unknown', 'Unknown event'),
        );
    }

    /**
     * @return array<string, string>
     */
    public function getDeliveryMethodOptions(?string $selected_method = null): array
    {
        $methods = $this->delivery_method_options ??= $this->buildDeliveryMethodOptions();

        if ($selected_method !== null && $selected_method !== '' && ! isset($methods[$selected_method])) {
            $methods[$selected_method] = $this->translate(
                'admin/orders/orders.shipping_methods.historical',
                'Historical delivery method',
            );
        }

        return $methods;
    }

    /**
     * @return array<string, string>
     */
    private function buildDeliveryMethodOptions(): array
    {
        $methods = [];

        if (is_enabled_singleton_module('NovaPoshta')) {
            $methods = [
                DeliveryMethodEnum::NovaPoshta->value => $this->translate('catalog/pages/checkout.delivery_methods.nova_poshta', 'Nova Poshta (Branch)'),
                DeliveryMethodEnum::NovaPoshtaCourier->value => $this->translate('catalog/pages/checkout.delivery_methods.nova_poshta_courier', 'Nova Poshta (Courier)'),
                DeliveryMethodEnum::NovaPoshtaPoshtomat->value => $this->translate('catalog/pages/checkout.delivery_methods.nova_poshta_poshtomat', 'Nova Poshta (Poshtomat)'),
            ];
        }

        if (is_enabled_singleton_module('UkrPoshta')) {
            $methods[DeliveryMethodEnum::UkrPoshta->value] = $this->translate('catalog/pages/checkout.delivery_methods.ukr_poshta', 'Ukr Poshta');
        }

        if (is_enabled_singleton_module('Pickup')) {
            $methods['pickup_store'] = $this->translate('pickup::storefront/checkout.delivery_method', 'Pickup from store');
        }

        return $methods;
    }

    /**
     * @return array<string, string>
     */
    public function getPaymentMethodOptions(string $locale, ?string $selected_method = null): array
    {
        $locale = Str::lower(Str::trim($locale));
        $methods = $this->payment_method_options[$locale] ??= $this->buildPaymentMethodOptions($locale);

        if ($selected_method !== null && $selected_method !== '' && ! isset($methods[$selected_method])) {
            $methods[$selected_method] = $this->translate(
                'admin/orders/orders.payment_methods.historical',
                'Historical payment method',
            );
        }

        return $methods;
    }

    /**
     * @return array<string, string>
     */
    private function buildPaymentMethodOptions(string $locale): array
    {
        $methods = [];
        $bank_transfer = $this->bank_transfer_module_data_service->getCheckoutData($locale);

        if ($bank_transfer['is_available']) {
            $methods[BankTransferConfig::PAYMENT_METHOD] = $bank_transfer['payment_name'];
        }

        $payment_upon_delivery = $this->payment_upon_delivery_module_data_service->getCheckoutData();

        if ($payment_upon_delivery['is_available']) {
            $methods[PaymentUponDeliveryConfig::PAYMENT_METHOD] = $this->translate(
                $payment_upon_delivery['label_translation_key'],
                'Payment upon delivery',
            );
        }

        $wayforpay = $this->wayforpay_module_data_service->getCheckoutData($locale);

        if ($wayforpay['is_available']) {
            $methods[$this->wayforpay_config->getPaymentMethod()] = $wayforpay['payment_name'];
        }

        return $methods;
    }

    /**
     * @return array<string, string>
     */
    public function getUserGroupOptions(): array
    {
        return $this->user_group_options ??= UserGroup::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function getCurrencyOptions(): array
    {
        return $this->currency_options ??= Currency::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('name')
            ->get(['id', 'code', 'name'])
            ->mapWithKeys(fn (Currency $currency): array => [
                (string) $currency->getKey() => sprintf(
                    '%s — %s',
                    $currency->code,
                    $currency->name,
                ),
            ])
            ->all();
    }

    /**
     * @param class-string<OrderStatuses|PaymentStatuses> $model
     * @return array<string, string>
     */
    private function getStatusOptions(string $model, ?int $selected_status_id): array
    {
        $cache_key = $model . ':' . ($selected_status_id ?? 'active');

        if (isset($this->status_options[$cache_key])) {
            return $this->status_options[$cache_key];
        }

        $language_id = $this->getCurrentLanguageId();

        $query = $model::query()
            ->with([
                'descriptions' => function ($description_query) use ($language_id): void {
                    $description_query->where('language_id', $language_id);
                },
            ])
            ->where(function (Builder $status_query) use ($selected_status_id): void {
                $status_query->where('is_active', true);

                if ($selected_status_id !== null) {
                    $status_query->orWhere('id', $selected_status_id);
                }
            })
            ->orderBy('sort_order');

        return $this->status_options[$cache_key] = $query->get()
            ->mapWithKeys(function (OrderStatuses|PaymentStatuses $status): array {
                $description = $status->descriptions->first();

                return [
                    (string) $status->getKey() => $description?->name
                        ?: $this->translate('admin/orders/orders.statuses.unnamed', 'Unnamed status'),
                ];
            })
            ->all();
    }

    public function getCurrentLanguageId(): ?int
    {
        if (! $this->language_id_resolved) {
            $this->language_id = $this->request_lookup_context
                ->getLanguageByCode(app()->getLocale())
                ?->getKey();
            $this->language_id_resolved = true;
        }

        return $this->language_id;
    }

    private function translate(string $key, string $fallback): string
    {
        $translation = __($key);

        return $translation === $key ? $fallback : (string) $translation;
    }
}
