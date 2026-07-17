<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Cart\CartRequestKeyEnum;
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
            'delivery_address' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        foreach (['first_name', 'last_name', 'phone', 'email', 'delivery_method', 'delivery_address'] as $key) {
            Arr::set($normalized_data, $key, Str::squish((string) $this->input($key, '')));
        }

        Arr::set($normalized_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value);
        Arr::set($normalized_data, 'payment_method', Str::lower(Str::squish((string) $this->input('payment_method', ''))));

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
        });
    }
}
