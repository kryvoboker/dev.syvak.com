<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Cart\CartRequestKeyEnum;
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
            'delivery_method' => ['required', 'string', Rule::in([
                'nova_poshta',
                'nova_poshta_poshtomat',
                'nova_poshta_courier',
                'ukr_poshta',
                PickupConfig::DELIVERY_METHOD,
            ])],
            'payment_method' => ['required', 'string', Rule::in([
                'cash_on_delivery',
                $wayforpay_payment_method,
                PaymentUponDeliveryConfig::PAYMENT_METHOD,
                BankTransferConfig::PAYMENT_METHOD,
            ])],
            'city' => ['nullable', 'array'],
            'city.city_description' => ['nullable', 'string', 'max:255'],
            'city.nova_poshta_city_id' => ['nullable', 'string', 'max:255'],
            'city.ukr_poshta_city_id' => ['nullable', 'integer', 'min:1'],
            'delivery_point' => ['nullable', 'array'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'promo_code' => ['nullable', 'string', 'max:255'],
            'no_call' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();
        $selection_state = app(CheckoutSelectionStateService::class)->getState();
        $selection_city = (array) Arr::get($selection_state, 'city', []);
        $selection_delivery_point = (array) Arr::get($selection_state, 'delivery_point', []);

        if ($selection_city !== []) {
            Arr::set($normalized_data, 'city', $selection_city);
        } else {
            Arr::set($normalized_data, 'city', [
                'city_description' => Str::squish((string) $this->input('city', '')),
            ]);
        }

        if ($selection_delivery_point !== []) {
            Arr::set($normalized_data, 'delivery_point', $selection_delivery_point);
        }

        if (
            filled(Arr::get($selection_state, 'delivery_address'))
            && blank($this->input('delivery_address'))
        ) {
            Arr::set($normalized_data, 'delivery_address', Arr::get($selection_state, 'delivery_address'));
        }

        foreach (['first_name', 'last_name', 'phone', 'email', 'delivery_method', 'delivery_address', 'comment', 'promo_code'] as $key) {
            Arr::set($normalized_data, $key, Str::squish((string) $this->input($key, '')));
        }

        Arr::set($normalized_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value);
        Arr::set($normalized_data, 'payment_method', Str::lower(Str::squish((string) $this->input('payment_method', ''))));
        Arr::set(
            $normalized_data,
            'delivery_point',
            (array) Arr::get($normalized_data, 'delivery_point', $this->input('delivery_point', [])),
        );
        Arr::set($normalized_data, 'no_call', $this->boolean('no_call'));

        $this->replace($normalized_data);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payment_method = (string) $this->input('payment_method', '');

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
                    'payment_method',
                    $message,
                );
            }

            $delivery_method = (string) $this->input('delivery_method', '');
            $delivery_point = (array) $this->input('delivery_point', []);
            $delivery_address = (string) $this->input('delivery_address', '');

            if ($delivery_method === 'nova_poshta_courier' && $delivery_address === '') {
                $validator->errors()->add('delivery_address', 'A delivery address is required.');
            }

            if (
                in_array($delivery_method, ['nova_poshta', 'nova_poshta_poshtomat', 'ukr_poshta'], true)
                && $delivery_point === []
            ) {
                $validator->errors()->add('delivery_point', 'A delivery point is required.');
            }
        });
    }
}
