<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\CartModeEnum;
use App\Enums\CartRequestKeyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class OrderConfirmValidateRequest extends FormRequest
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
            'first_name'                        => ['required', 'string', 'min:2', 'max:255'],
            'last_name'                         => ['required', 'string', 'min:2', 'max:255'],
            'phone'                             => ['required', 'string', 'min:10', 'max:30'],
            CartRequestKeyEnum::CartMode->value => ['nullable', 'string', Rule::in(array_column(CartModeEnum::cases(), 'value'))],
            'payment_method'                    => ['nullable', 'string', 'in:cash_on_delivery,wayforpay'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        Arr::set($normalized_data, 'first_name', Str::trim((string) $this->input('first_name', '')));
        Arr::set($normalized_data, 'last_name', Str::trim((string) $this->input('last_name', '')));
        Arr::set($normalized_data, 'phone', Str::trim((string) $this->input('phone', '')));
        $cart_mode = CartModeEnum::tryFrom(
            Str::lower((string) $this->input(CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value)),
        );

        Arr::set(
            $normalized_data,
            CartRequestKeyEnum::CartMode->value,
            $cart_mode === null ? CartModeEnum::Regular->value : $cart_mode->value,
        );
        Arr::set($normalized_data, 'payment_method', Str::lower((string) $this->input('payment_method', 'cash_on_delivery')));

        $this->replace($normalized_data);
    }
}
