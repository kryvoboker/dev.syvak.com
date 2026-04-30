<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

use App\Enums\CartModeEnum;
use App\Enums\CartRequestKeyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CartUpdateRequest extends FormRequest
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
        return [
            'cart_id'                                  => ['required', 'integer', 'min:1'],
            'quantity'                                 => ['required', 'integer', 'min:1', 'max:999'],
            CartRequestKeyEnum::CartMode->value        => ['nullable', 'string', Rule::in(array_column(CartModeEnum::cases(), 'value'))],
            CartRequestKeyEnum::IsCallFromModal->value => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        $cart_id   = $this->input('cart_id', $this->route('cart_id'));
        $quantity  = $this->input('quantity', 1);
        $cart_mode = Str::lower((string) $this->input(CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value));

        Arr::set($normalized_data, 'cart_id', is_numeric($cart_id) ? (int) $cart_id : $cart_id);
        Arr::set($normalized_data, 'quantity', is_numeric($quantity) ? (int) $quantity : $quantity);
        $allowed_modes = array_column(CartModeEnum::cases(), 'value');
        Arr::set($normalized_data, CartRequestKeyEnum::CartMode->value, in_array($cart_mode, $allowed_modes, true) ? $cart_mode : CartModeEnum::Regular->value);
        Arr::set($normalized_data, CartRequestKeyEnum::IsCallFromModal->value, $this->boolean(CartRequestKeyEnum::IsCallFromModal->value));

        $this->replace($normalized_data);
    }
}
