<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Enums\Order\OrderDataKeyEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CheckoutSelectionStateService
{
    private const string SESSION_KEY = 'checkout.selection_state';

    public function __construct(
        private readonly Request $request,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(): array
    {
        if (! $this->request->hasSession()) {
            return [];
        }

        $state = $this->request->session()->get(self::SESSION_KEY, []);

        return is_array($state) ? $state : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function replaceState(array $payload): array
    {
        $state = $this->normalizeState($payload);

        if ($this->request->hasSession()) {
            $this->request->session()->put(self::SESSION_KEY, $state);
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $city
     * @return array<string, mixed>
     */
    public function setCity(array $city): array
    {
        $state = $this->getState();
        $state[OrderDataKeyEnum::City->value] = $this->normalizeRow($city);

        return $this->replaceState($state);
    }

    /** @return array<string, mixed> */
    public function setDeliveryMethod(string $delivery_method): array
    {
        $state = $this->getState();
        $state[OrderDataKeyEnum::DeliveryMethod->value] = Str::lower(Str::squish($delivery_method));

        return $this->replaceState($state);
    }

    /**
     * @param  mixed  $delivery_address
     * @return array<string, mixed>
     */
    public function setDeliveryAddress(mixed $delivery_address): array
    {
        $state = $this->getState();
        $state[OrderDataKeyEnum::DeliveryAddress->value] = $this->normalizeDeliveryAddress($delivery_address);

        return $this->replaceState($state);
    }

    /**
     * @param  array<string, mixed>  $delivery_point
     * @return array<string, mixed>
     */
    public function setDeliveryPoint(array $delivery_point): array
    {
        $state = $this->getState();
        $state[OrderDataKeyEnum::DeliveryPoint->value] = $this->normalizeRow($delivery_point);

        return $this->replaceState($state);
    }

    /** @return array<string, mixed> */
    public function clearDeliveryPoint(): array
    {
        $state = $this->getState();
        $state[OrderDataKeyEnum::DeliveryPoint->value] = [];

        return $this->replaceState($state);
    }

    /** @return array<string, mixed> */
    public function clearDeliveryAddress(): array
    {
        $state = $this->getState();
        $state[OrderDataKeyEnum::DeliveryAddress->value] = '';

        return $this->replaceState($state);
    }

    public function clear(): void
    {
        if ($this->request->hasSession()) {
            $this->request->session()->forget(self::SESSION_KEY);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeState(array $payload): array
    {
        $current_state = $this->getState();

        return [
            OrderDataKeyEnum::DeliveryMethod->value => Str::lower(Str::squish($this->stringValue(Arr::get($payload, OrderDataKeyEnum::DeliveryMethod->value, '')))),
            OrderDataKeyEnum::PaymentMethod->value => Str::lower(Str::squish($this->stringValue(Arr::get($payload, OrderDataKeyEnum::PaymentMethod->value, '')))),
            OrderDataKeyEnum::City->value => $this->normalizeRow($this->stringKeyedArray(Arr::get($payload, OrderDataKeyEnum::City->value, []))),
            OrderDataKeyEnum::DeliveryPoint->value => $this->normalizeRow($this->stringKeyedArray(Arr::get($payload, OrderDataKeyEnum::DeliveryPoint->value, []))),
            OrderDataKeyEnum::DeliveryAddress->value => $this->normalizeDeliveryAddress(Arr::get($payload, OrderDataKeyEnum::DeliveryAddress->value, '')),
            'first_name' => $this->normalizeText($payload, $current_state, 'first_name'),
            'last_name' => $this->normalizeText($payload, $current_state, 'last_name'),
            'phone' => $this->normalizeText($payload, $current_state, 'phone'),
            'email' => $this->normalizeText($payload, $current_state, 'email'),
            OrderDataKeyEnum::Comment->value => $this->normalizeText($payload, $current_state, OrderDataKeyEnum::Comment->value),
            OrderDataKeyEnum::PromoCode->value => $this->normalizeText($payload, $current_state, OrderDataKeyEnum::PromoCode->value),
            OrderDataKeyEnum::NoCall->value => $this->normalizeBoolean($payload, $current_state, OrderDataKeyEnum::NoCall->value),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $current_state
     */
    private function normalizeText(array $payload, array $current_state, string $key): string
    {
        $value = Arr::has($payload, $key) ? Arr::get($payload, $key) : Arr::get($current_state, $key, '');

        return Str::squish($this->stringValue($value));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $current_state
     */
    private function normalizeBoolean(array $payload, array $current_state, string $key): bool
    {
        $value = Arr::has($payload, $key) ? Arr::get($payload, $key) : Arr::get($current_state, $key, false);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  mixed  $delivery_address
     */
    private function normalizeDeliveryAddress(mixed $delivery_address): string
    {
        if (is_array($delivery_address)) {
            return '';
        }

        return Str::squish($this->stringValue($delivery_address));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        if ($row === []) {
            return [];
        }

        return Collection::make($row)
            ->only([
                'id',
                'region_id',
                'district_id',
                'city_id',
                'poregion_id',
                'podistrict_id',
                'pdcity_id',
                'postcode',
                'city_description',
                'nova_poshta_city_id',
                'ukr_poshta_city_id',
                'city_lat',
                'city_lng',
                'description',
                'ref',
                'city_ref',
                'city_name',
                'city_ua',
                'region',
                'region_description',
                'region_ua',
                'district_ua',
                'number',
                'site_key',
                'latitude',
                'longitude',
                'schedule',
                'delivery_method',
                'type',
                'branch_value',
                'branch_label',
                'label',
                'lock_code',
                'postreet_id',
            ])
            ->filter(fn (mixed $value): bool => ! is_null($value) && $value !== '')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function stringKeyedArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
