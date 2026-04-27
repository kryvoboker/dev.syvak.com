<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CartDeleteRequest extends FormRequest
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
            'product_variant_id' => ['required', 'integer', 'min:1'],
            'cart_mode'          => ['nullable', 'string', 'in:regular,fast_order'],
            'is_call_from_modal' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        $variant_id = $this->input('product_variant_id', $this->route('cart_id'));
        $cart_mode  = Str::lower((string) $this->input('cart_mode', 'regular'));

        Arr::set($normalized_data, 'product_variant_id', is_numeric($variant_id) ? (int) $variant_id : $variant_id);
        Arr::set($normalized_data, 'cart_mode', in_array($cart_mode, ['regular', 'fast_order'], true) ? $cart_mode : 'regular');
        Arr::set($normalized_data, 'is_call_from_modal', $this->boolean('is_call_from_modal', false));

        $this->replace($normalized_data);
    }
}
