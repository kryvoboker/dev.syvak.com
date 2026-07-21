<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Cart\CartRequestKeyEnum;
use App\Enums\Order\DeliveryMethodEnum;
use App\Enums\Order\OrderDataKeyEnum;
use App\Enums\Order\PaymentMethodEnum;
use App\Services\Checkout\CheckoutSelectionStateService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\BankTransfer\Services\BankTransferModuleDataService;
use Modules\BankTransfer\Support\BankTransferConfig;
use Modules\PaymentUponDelivery\Services\PaymentUponDeliveryModuleDataService;
use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;
use Modules\Pickup\Support\PickupConfig;
use Modules\WayForPay\Services\WayForPayModuleDataService;
use Modules\WayForPay\Support\WayForPayConfig;

class SimpleOrderValidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $wayforpay_payment_method = app(WayForPayConfig::class)->getPaymentMethod();

        return [
            'first_name' => ['required', 'string', 'min:2', 'max:255'],
            'last_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            CartRequestKeyEnum::CartMode->value => ['required', 'string', Rule::in([CartModeEnum::Regular->value])],
            OrderDataKeyEnum::DeliveryMethod->value => ['required', 'string', Rule::in([
                DeliveryMethodEnum::NovaPoshta->value,
                DeliveryMethodEnum::NovaPoshtaPoshtomat->value,
                DeliveryMethodEnum::NovaPoshtaCourier->value,
                DeliveryMethodEnum::UkrPoshta->value,
                PickupConfig::DELIVERY_METHOD,
            ])],
            OrderDataKeyEnum::PaymentMethod->value => ['required', 'string', Rule::in([
                PaymentMethodEnum::CashOnDelivery->value,
                $wayforpay_payment_method,
                PaymentUponDeliveryConfig::PAYMENT_METHOD,
                BankTransferConfig::PAYMENT_METHOD,
            ])],
            'city' => ['nullable', 'array'],
            'city.city_description' => ['nullable', 'string', 'max:255'],
            'city.nova_poshta_city_id' => ['nullable', 'string', 'max:255'],
            'city.ukr_poshta_city_id' => ['nullable', 'integer', 'min:1'],
            OrderDataKeyEnum::DeliveryPoint->value => ['nullable', 'array'],
            OrderDataKeyEnum::DeliveryAddress->value => ['nullable', 'string', 'max:255'],
            OrderDataKeyEnum::Comment->value => ['nullable', 'string', 'max:5000'],
            OrderDataKeyEnum::PromoCode->value => ['nullable', 'string', 'max:255'],
            OrderDataKeyEnum::NoCall->value => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();
        $selection_state = app(CheckoutSelectionStateService::class)->getState();
        $selection_city = (array) Arr::get($selection_state, 'city', []);
            $selection_delivery_point = (array) Arr::get($selection_state, OrderDataKeyEnum::DeliveryPoint->value, []);

        if ($selection_city !== []) {
            Arr::set($normalized_data, 'city', $selection_city);
        } else {
            Arr::set($normalized_data, 'city', [
                'city_description' => Str::squish((string) $this->input('city', '')),
            ]);
        }

        if ($selection_delivery_point !== []) {
            Arr::set($normalized_data, OrderDataKeyEnum::DeliveryPoint->value, $selection_delivery_point);
        }

        if (
            filled(Arr::get($selection_state, OrderDataKeyEnum::DeliveryAddress->value))
            && blank($this->input(OrderDataKeyEnum::DeliveryAddress->value))
        ) {
            Arr::set($normalized_data, OrderDataKeyEnum::DeliveryAddress->value, Arr::get($selection_state, OrderDataKeyEnum::DeliveryAddress->value));
        }

        foreach (['first_name', 'last_name', 'phone', 'email', OrderDataKeyEnum::DeliveryMethod->value, OrderDataKeyEnum::DeliveryAddress->value, OrderDataKeyEnum::Comment->value, OrderDataKeyEnum::PromoCode->value] as $key) {
            Arr::set($normalized_data, $key, Str::squish((string) $this->input($key, '')));
        }

        Arr::set($normalized_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value);
        Arr::set($normalized_data, OrderDataKeyEnum::PaymentMethod->value, Str::lower(Str::squish((string) $this->input(OrderDataKeyEnum::PaymentMethod->value, ''))));
        Arr::set(
            $normalized_data,
            OrderDataKeyEnum::DeliveryPoint->value,
            (array) Arr::get($normalized_data, OrderDataKeyEnum::DeliveryPoint->value, $this->input(OrderDataKeyEnum::DeliveryPoint->value, [])),
        );
        Arr::set($normalized_data, OrderDataKeyEnum::NoCall->value, $this->boolean(OrderDataKeyEnum::NoCall->value));

        $this->replace($normalized_data);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payment_method = (string) $this->input(OrderDataKeyEnum::PaymentMethod->value, '');

            if ($payment_method === '') {
                return;
            }

            $payment_data = match ($payment_method) {
                app(WayForPayConfig::class)->getPaymentMethod() => app(WayForPayModuleDataService::class)->getCheckoutData(normalize_locale(null)),
                BankTransferConfig::PAYMENT_METHOD => app(BankTransferModuleDataService::class)->getCheckoutData(normalize_locale(null)),
                PaymentUponDeliveryConfig::PAYMENT_METHOD => app(PaymentUponDeliveryModuleDataService::class)->getCheckoutData(),
                default => ['is_available' => true],
            };

            if (($payment_data['is_available'] ?? false) !== true) {
                $message = match ($payment_method) {
                    app(WayForPayConfig::class)->getPaymentMethod() => __('wayforpay::storefront/checkout.validation.payment_method_unavailable'),
                    BankTransferConfig::PAYMENT_METHOD => __('banktransfer::storefront/checkout.validation.payment_method_unavailable'),
                    PaymentUponDeliveryConfig::PAYMENT_METHOD => __('paymentupondelivery::storefront/checkout.validation.payment_method_unavailable'),
                    default => 'The selected payment method is unavailable.',
                };

                $validator->errors()->add(
                    OrderDataKeyEnum::PaymentMethod->value,
                    $message,
                );
            }

            $delivery_method = (string) $this->input(OrderDataKeyEnum::DeliveryMethod->value, '');
            $delivery_point = (array) $this->input(OrderDataKeyEnum::DeliveryPoint->value, []);
            $delivery_address = (string) $this->input(OrderDataKeyEnum::DeliveryAddress->value, '');

            if ($delivery_method === DeliveryMethodEnum::NovaPoshtaCourier->value && $delivery_address === '') {
                $validator->errors()->add(OrderDataKeyEnum::DeliveryAddress->value, 'A delivery address is required.');
            }

            if (
                in_array($delivery_method, [
                    DeliveryMethodEnum::NovaPoshta->value,
                    DeliveryMethodEnum::NovaPoshtaPoshtomat->value,
                    DeliveryMethodEnum::UkrPoshta->value,
                ], true)
                && $delivery_point === []
            ) {
                $validator->errors()->add(OrderDataKeyEnum::DeliveryPoint->value, 'A delivery point is required.');
            }
        });
    }
}
