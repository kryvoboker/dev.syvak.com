<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

use App\Enums\Order\DeliveryMethodEnum;
use App\Enums\Order\OrderDataKeyEnum;
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
use Modules\WayForPay\Services\WayForPayModuleDataService;
use Modules\WayForPay\Support\WayForPayConfig;

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
            OrderDataKeyEnum::DeliveryMethod->value => ['nullable', 'string', Rule::in([
                DeliveryMethodEnum::NovaPoshta->value,
                DeliveryMethodEnum::NovaPoshtaPoshtomat->value,
                DeliveryMethodEnum::NovaPoshtaCourier->value,
                DeliveryMethodEnum::UkrPoshta->value,
                PickupConfig::DELIVERY_METHOD,
            ])],
            OrderDataKeyEnum::PaymentMethod->value => ['nullable', 'string', 'max:100'],
            OrderDataKeyEnum::City->value => ['nullable', 'array'],
            'city.city_description' => ['nullable', 'string', 'max:255'],
            'city.nova_poshta_city_id' => ['nullable', 'string', 'max:255'],
            'city.ukr_poshta_city_id' => ['nullable', 'integer', 'min:1'],
            'city.city_lat' => ['nullable', 'numeric'],
            'city.city_lng' => ['nullable', 'numeric'],
            OrderDataKeyEnum::DeliveryPoint->value => ['nullable', 'array'],
            OrderDataKeyEnum::DeliveryAddress->value => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            OrderDataKeyEnum::Comment->value => ['nullable', 'string', 'max:5000'],
            OrderDataKeyEnum::PromoCode->value => ['nullable', 'string', 'max:255'],
            OrderDataKeyEnum::NoCall->value => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = $this->all();

        $delivery_method = Str::lower(Str::squish((string) $this->input(OrderDataKeyEnum::DeliveryMethod->value, '')));
        Arr::set($normalized_data, OrderDataKeyEnum::DeliveryMethod->value, $delivery_method !== '' ? $delivery_method : null);

        $payment_method = Str::lower(Str::squish((string) $this->input(OrderDataKeyEnum::PaymentMethod->value, '')));
        Arr::set($normalized_data, OrderDataKeyEnum::PaymentMethod->value, $payment_method !== '' ? $payment_method : null);

        $city = (array) $this->input('city', []);
        Arr::set($normalized_data, 'city.city_description', Str::squish((string) Arr::get($city, 'city_description', '')));
        Arr::set($normalized_data, 'city.nova_poshta_city_id', Str::squish((string) Arr::get($city, 'nova_poshta_city_id', '')));

        $ukr_poshta_city_id = Arr::get($city, 'ukr_poshta_city_id');
        Arr::set($normalized_data, 'city.ukr_poshta_city_id', is_numeric($ukr_poshta_city_id) ? (int) $ukr_poshta_city_id : null);

        $city_lat = Arr::get($city, 'city_lat');
        Arr::set($normalized_data, 'city.city_lat', is_numeric($city_lat) ? (float) $city_lat : null);

        $city_lng = Arr::get($city, 'city_lng');
        Arr::set($normalized_data, 'city.city_lng', is_numeric($city_lng) ? (float) $city_lng : null);

        Arr::set($normalized_data, OrderDataKeyEnum::DeliveryPoint->value, (array) $this->input(OrderDataKeyEnum::DeliveryPoint->value, []));
        Arr::set(
            $normalized_data,
            OrderDataKeyEnum::DeliveryAddress->value,
            Str::squish((string) $this->input(OrderDataKeyEnum::DeliveryAddress->value, '')) !== ''
                ? Str::squish((string) $this->input(OrderDataKeyEnum::DeliveryAddress->value, ''))
                : null,
        );

        foreach (['first_name', 'last_name', 'phone', 'email', OrderDataKeyEnum::Comment->value, OrderDataKeyEnum::PromoCode->value] as $key) {
            Arr::set($normalized_data, $key, Str::squish((string) $this->input($key, '')));
        }

        Arr::set($normalized_data, OrderDataKeyEnum::NoCall->value, $this->boolean(OrderDataKeyEnum::NoCall->value));

        $this->replace($normalized_data);
    }

    /**
     * Add module-specific availability validation after the common payload rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $payment_method = (string) $this->input(OrderDataKeyEnum::PaymentMethod->value, '');

            if ($payment_method !== '' && ! $this->isAvailablePaymentMethod($payment_method)) {
                $validator->errors()->add(
                    'payment_method',
                    match ($payment_method) {
                        app(WayForPayConfig::class)->getPaymentMethod() => __('wayforpay::storefront/checkout.validation.payment_method_unavailable'),
                        BankTransferConfig::PAYMENT_METHOD => __('banktransfer::storefront/checkout.validation.payment_method_unavailable'),
                        default => __('paymentupondelivery::storefront/checkout.validation.payment_method_unavailable'),
                    },
                );
            }

            if ($this->input(OrderDataKeyEnum::DeliveryMethod->value) !== PickupConfig::DELIVERY_METHOD) {
                return;
            }

            $active_language_codes = (new Language())
                ->getActiveLanguages()
                ->pluck('code')
                ->map(fn (mixed $code): string => strtolower((string) $code))
                ->values()
                ->all();

            if (! is_enabled_singleton_module('Pickup') || ! app(PickupConfig::class)->isComplete($active_language_codes)) {
                $validator->errors()->add(OrderDataKeyEnum::DeliveryMethod->value, 'Pickup store delivery is not available.');
            }
        });
    }

    private function isAvailablePaymentMethod(string $payment_method): bool
    {
        $payment_data = match ($payment_method) {
            app(WayForPayConfig::class)->getPaymentMethod() => app(WayForPayModuleDataService::class)->getCheckoutData(normalize_locale(null)),
            BankTransferConfig::PAYMENT_METHOD => app(BankTransferModuleDataService::class)->getCheckoutData(normalize_locale(null)),
            default => app(PaymentUponDeliveryModuleDataService::class)->getCheckoutData(),
        };

        return ($payment_data['is_available'] ?? false) === true
            && ($payment_data['payment_method'] ?? '') === $payment_method;
    }
}
