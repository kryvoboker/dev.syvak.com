<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

use App\Models\ApplicationSettings\Language;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\BankTransfer\Services\BankTransferModuleDataService;
use Modules\BankTransfer\Support\BankTransferConfig;
use Modules\PaymentUponDelivery\Services\PaymentUponDeliveryModuleDataService;
use Modules\Pickup\Support\PickupConfig;

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
            'delivery_method' => ['nullable', 'string', Rule::in(['nova_poshta', 'nova_poshta_poshtomat', 'nova_poshta_courier', 'ukr_poshta', PickupConfig::DELIVERY_METHOD])],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'array'],
            'city.city_description' => ['nullable', 'string', 'max:255'],
            'city.nova_poshta_city_id' => ['nullable', 'string', 'max:255'],
            'city.ukr_poshta_city_id' => ['nullable', 'integer', 'min:1'],
            'city.city_lat' => ['nullable', 'numeric'],
            'city.city_lng' => ['nullable', 'numeric'],
            'delivery_point' => ['nullable', 'array'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        $delivery_method = Str::lower(Str::squish((string) $this->input('delivery_method', '')));
        Arr::set($normalized_data, 'delivery_method', $delivery_method !== '' ? $delivery_method : null);

        $payment_method = Str::lower(Str::squish((string) $this->input('payment_method', '')));
        Arr::set($normalized_data, 'payment_method', $payment_method !== '' ? $payment_method : null);

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
        Arr::set(
            $normalized_data,
            'delivery_address',
            Str::squish((string) $this->input('delivery_address', '')) !== ''
                ? Str::squish((string) $this->input('delivery_address', ''))
                : null,
        );

        $this->replace($normalized_data);
    }

    /**
     * Add module-specific availability validation after the common payload rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $payment_method = (string) $this->input('payment_method', '');

            if ($payment_method !== '' && ! $this->isAvailablePaymentMethod($payment_method)) {
                $validator->errors()->add(
                    'payment_method',
                    $payment_method === BankTransferConfig::PAYMENT_METHOD
                        ? __('banktransfer::storefront/checkout.validation.payment_method_unavailable')
                        : __('paymentupondelivery::storefront/checkout.validation.payment_method_unavailable'),
                );
            }

            if ($this->input('delivery_method') !== PickupConfig::DELIVERY_METHOD) {
                return;
            }

            $active_language_codes = (new Language())
                ->getActiveLanguages()
                ->pluck('code')
                ->map(fn (mixed $code): string => strtolower((string) $code))
                ->values()
                ->all();

            if (! is_enabled_singleton_module('Pickup') || ! app(PickupConfig::class)->isComplete($active_language_codes)) {
                $validator->errors()->add('delivery_method', 'Pickup store delivery is not available.');
            }
        });
    }

    private function isAvailablePaymentMethod(string $payment_method): bool
    {
        $payment_data = $payment_method === BankTransferConfig::PAYMENT_METHOD
            ? app(BankTransferModuleDataService::class)->getCheckoutData(normalize_locale(null))
            : app(PaymentUponDeliveryModuleDataService::class)->getCheckoutData();

        return ($payment_data['is_available'] ?? false) === true
            && ($payment_data['payment_method'] ?? '') === $payment_method;
    }
}
