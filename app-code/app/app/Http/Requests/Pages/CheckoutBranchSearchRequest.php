<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

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
            'branch_keyword' => ['required', 'string', 'min:1', 'max:255'],
            'delivery_method' => ['required', 'string', Rule::in(['nova_poshta', 'nova_poshta_poshtomat', 'nova_poshta_courier', 'ukr_poshta'])],
            'city' => ['required', 'array'],
            'city.nova_poshta_city_id' => ['nullable', 'string', 'max:255', 'required_if:delivery_method,nova_poshta,nova_poshta_poshtomat,nova_poshta_courier'],
            'city.ukr_poshta_city_id' => ['nullable', 'integer', 'min:1', 'required_if:delivery_method,ukr_poshta'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        Arr::set($normalized_data, 'branch_keyword', Str::squish((string) $this->input('branch_keyword', '')));

        $delivery_method = Str::lower(Str::squish((string) $this->input('delivery_method', '')));
        Arr::set($normalized_data, 'delivery_method', $delivery_method !== '' ? $delivery_method : null);

        $city = (array) $this->input('city', []);
        Arr::set($normalized_data, 'city.nova_poshta_city_id', Str::squish((string) Arr::get($city, 'nova_poshta_city_id', '')));

        $ukr_poshta_city_id = Arr::get($city, 'ukr_poshta_city_id');
        Arr::set($normalized_data, 'city.ukr_poshta_city_id', is_numeric($ukr_poshta_city_id) ? (int) $ukr_poshta_city_id : null);

        $this->replace($normalized_data);
    }
}
