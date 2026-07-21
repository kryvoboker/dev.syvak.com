<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

use App\Enums\Order\DeliveryMethodEnum;
use App\Enums\Order\OrderDataKeyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutBranchSearchRequest extends FormRequest
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
            OrderDataKeyEnum::DeliveryMethod->value => ['required', 'string', Rule::in([
                DeliveryMethodEnum::NovaPoshta->value,
                DeliveryMethodEnum::NovaPoshtaPoshtomat->value,
                DeliveryMethodEnum::UkrPoshta->value,
            ])],
            OrderDataKeyEnum::City->value => ['required', 'array'],
            'city.nova_poshta_city_id' => ['nullable', 'string', 'max:255', 'required_if:delivery_method,nova_poshta,nova_poshta_poshtomat'],
            'city.ukr_poshta_city_id' => ['nullable', 'integer', 'min:1', 'required_if:delivery_method,ukr_poshta'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        $delivery_method = Str::lower(Str::squish((string) $this->input(OrderDataKeyEnum::DeliveryMethod->value, '')));
        Arr::set($normalized_data, OrderDataKeyEnum::DeliveryMethod->value, $delivery_method !== '' ? $delivery_method : null);

        $city = (array) $this->input('city', []);
        Arr::set($normalized_data, 'city.nova_poshta_city_id', Str::squish((string) Arr::get($city, 'nova_poshta_city_id', '')));

        $ukr_poshta_city_id = Arr::get($city, 'ukr_poshta_city_id');
        Arr::set($normalized_data, 'city.ukr_poshta_city_id', is_numeric($ukr_poshta_city_id) ? (int) $ukr_poshta_city_id : null);

        $this->replace($normalized_data);
    }
}
