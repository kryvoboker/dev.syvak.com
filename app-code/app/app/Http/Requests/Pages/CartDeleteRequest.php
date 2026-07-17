<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Cart\CartRequestKeyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CartDeleteRequest extends FormRequest
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
            'cart_id' => ['required', 'integer', 'min:1'],
            CartRequestKeyEnum::CartMode->value => ['nullable', 'string', Rule::in(array_column(CartModeEnum::cases(), 'value'))],
            CartRequestKeyEnum::IsCallFromModal->value => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        $cart_id = $this->input('cart_id', $this->route('cart_id'));
        $cart_mode = Str::lower((string) $this->input(CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value));

        Arr::set($normalized_data, 'cart_id', is_numeric($cart_id) ? (int) $cart_id : $cart_id);
        $allowed_modes = array_column(CartModeEnum::cases(), 'value');
        Arr::set($normalized_data, CartRequestKeyEnum::CartMode->value, in_array($cart_mode, $allowed_modes, true) ? $cart_mode : CartModeEnum::Regular->value);
        Arr::set($normalized_data, CartRequestKeyEnum::IsCallFromModal->value, $this->boolean(CartRequestKeyEnum::IsCallFromModal->value));

        $this->replace($normalized_data);
    }
}
