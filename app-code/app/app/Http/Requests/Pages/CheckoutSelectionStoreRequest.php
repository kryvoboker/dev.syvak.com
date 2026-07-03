<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutSelectionStoreRequest extends FormRequest
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
            'delivery_method' => ['nullable', 'string', Rule::in(['nova_poshta', 'ukr_poshta'])],
            'city' => ['nullable', 'array'],
            'city.city_description' => ['nullable', 'string', 'max:255'],
            'city.nova_poshta_city_id' => ['nullable', 'string', 'max:255'],
            'city.ukr_poshta_city_id' => ['nullable', 'integer', 'min:1'],
            'city.city_lat' => ['nullable', 'numeric'],
            'city.city_lng' => ['nullable', 'numeric'],
            'delivery_point' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        $delivery_method = Str::lower(Str::squish((string) $this->input('delivery_method', '')));
        Arr::set($normalized_data, 'delivery_method', $delivery_method !== '' ? $delivery_method : null);

        $city = (array) $this->input('city', []);
        Arr::set($normalized_data, 'city.city_description', Str::squish((string) Arr::get($city, 'city_description', '')));
        Arr::set($normalized_data, 'city.nova_poshta_city_id', Str::squish((string) Arr::get($city, 'nova_poshta_city_id', '')));

        $ukr_poshta_city_id = Arr::get($city, 'ukr_poshta_city_id');
        Arr::set($normalized_data, 'city.ukr_poshta_city_id', is_numeric($ukr_poshta_city_id) ? (int) $ukr_poshta_city_id : null);

        $city_lat = Arr::get($city, 'city_lat');
        Arr::set($normalized_data, 'city.city_lat', is_numeric($city_lat) ? (float) $city_lat : null);

        $city_lng = Arr::get($city, 'city_lng');
        Arr::set($normalized_data, 'city.city_lng', is_numeric($city_lng) ? (float) $city_lng : null);

        Arr::set($normalized_data, 'delivery_point', (array) $this->input('delivery_point', []));

        $this->replace($normalized_data);
    }
}
