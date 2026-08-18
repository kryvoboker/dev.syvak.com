<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Cart\CartRequestKeyEnum;
use App\Enums\Order\OrderDataKeyEnum;
use App\Enums\Order\PaymentMethodEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Modules\BankTransfer\Services\Storefront\BankTransferModuleDataService;
use Modules\BankTransfer\Support\BankTransferConfig;
use Modules\PaymentUponDelivery\Services\Storefront\PaymentUponDeliveryModuleDataService;
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
            OrderDataKeyEnum::PaymentMethod->value => ['required', 'string', 'in:' . implode(',', [
                PaymentMethodEnum::CashOnDelivery->value,
                PaymentUponDeliveryConfig::PAYMENT_METHOD,
                BankTransferConfig::PAYMENT_METHOD,
            ])],
        ];
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        Arr::set($normalized_data, 'first_name', Str::trim($this->stringValue($this->input('first_name', ''))));
        Arr::set($normalized_data, 'last_name', Str::trim($this->stringValue($this->input('last_name', ''))));
        Arr::set($normalized_data, 'phone', Str::trim($this->stringValue($this->input('phone', ''))));
        Arr::set($normalized_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::FastOrder->value);
        Arr::set($normalized_data, OrderDataKeyEnum::PaymentMethod->value, Str::lower($this->stringValue($this->input(OrderDataKeyEnum::PaymentMethod->value, ''))));

        $this->replace($normalized_data);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payment_method = $this->stringValue($this->input(OrderDataKeyEnum::PaymentMethod->value, ''));

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

            if ($payment_data['is_available'] !== true) {
                $validator->errors()->add(
                    OrderDataKeyEnum::PaymentMethod->value,
                    $payment_method === BankTransferConfig::PAYMENT_METHOD
                        ? __('banktransfer::storefront/checkout.validation.payment_method_unavailable')
                        : __('paymentupondelivery::storefront/checkout.validation.payment_method_unavailable'),
                );
            }
        });
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
