<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\CartModeEnum;
use App\Enums\CartRequestKeyEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Modules\BankTransfer\Services\BankTransferModuleDataService;
use Modules\BankTransfer\Support\BankTransferConfig;
use Modules\PaymentUponDelivery\Services\PaymentUponDeliveryModuleDataService;
use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;

class FastOrderValidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, In|string>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:255'],
            'last_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:30'],
            CartRequestKeyEnum::CartMode->value => ['required', 'string', Rule::in([CartModeEnum::FastOrder->value])],
            'payment_method' => ['required', 'string', 'in:cash_on_delivery,wayforpay,' . PaymentUponDeliveryConfig::PAYMENT_METHOD . ',' . BankTransferConfig::PAYMENT_METHOD],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        Arr::set($normalized_data, 'first_name', Str::trim((string) $this->input('first_name', '')));
        Arr::set($normalized_data, 'last_name', Str::trim((string) $this->input('last_name', '')));
        Arr::set($normalized_data, 'phone', Str::trim((string) $this->input('phone', '')));
        Arr::set($normalized_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::FastOrder->value);
        Arr::set($normalized_data, 'payment_method', Str::lower((string) $this->input('payment_method', '')));

        $this->replace($normalized_data);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payment_method = (string) $this->input('payment_method', '');

            $is_supported_payment_method = in_array(
                $payment_method,
                [
                    PaymentUponDeliveryConfig::PAYMENT_METHOD,
                    BankTransferConfig::PAYMENT_METHOD,
                ],
                true,
            );

            if ($is_supported_payment_method === false) {
                return;
            }

            $payment_data = $payment_method === BankTransferConfig::PAYMENT_METHOD
                ? app(BankTransferModuleDataService::class)->getCheckoutData(normalize_locale(null))
                : app(PaymentUponDeliveryModuleDataService::class)->getCheckoutData();

            if (($payment_data['is_available'] ?? false) !== true) {
                $validator->errors()->add(
                    'payment_method',
                    $payment_method === BankTransferConfig::PAYMENT_METHOD
                        ? __('banktransfer::storefront/checkout.validation.payment_method_unavailable')
                        : __('paymentupondelivery::storefront/checkout.validation.payment_method_unavailable'),
                );
            }
        });
    }
}
