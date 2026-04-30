<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\CartModeEnum;
use App\Enums\CartRequestKeyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderConfirmValidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
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
        Arr::set(
            $normalized_data,
            CartRequestKeyEnum::CartMode->value,
            CartModeEnum::tryFrom(Str::lower((string) $this->input(CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value)))?->value
            ?? CartModeEnum::Regular->value,
        );
        Arr::set($normalized_data, 'payment_method', Str::lower((string) $this->input('payment_method', 'cash_on_delivery')));

        $this->replace($normalized_data);
    }
}
