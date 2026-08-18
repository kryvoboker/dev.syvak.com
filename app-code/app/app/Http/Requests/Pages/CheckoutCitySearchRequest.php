<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CheckoutCitySearchRequest extends FormRequest
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
            'city_keyword' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        $city_keyword = $this->input('city_keyword', '');
        Arr::set($normalized_data, 'city_keyword', Str::squish(is_scalar($city_keyword) ? (string) $city_keyword : ''));

        $this->replace($normalized_data);
    }
}
