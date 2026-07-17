<?php

declare(strict_types=1);

namespace App\Services\Checkout;

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
        $state['city'] = $this->normalizeRow($city);

        return $this->replaceState($state);
    }

    public function setDeliveryMethod(string $delivery_method): array
    {
        $state = $this->getState();
        $state['delivery_method'] = Str::lower(Str::squish($delivery_method));

        return $this->replaceState($state);
    }

    /**
     * @param  mixed  $delivery_address
     * @return array<string, mixed>
     */
    public function setDeliveryAddress(mixed $delivery_address): array
    {
        $state = $this->getState();
        $state['delivery_address'] = $this->normalizeDeliveryAddress($delivery_address);

        return $this->replaceState($state);
    }

    /**
     * @param  array<string, mixed>  $delivery_point
     * @return array<string, mixed>
     */
    public function setDeliveryPoint(array $delivery_point): array
    {
        $state = $this->getState();
        $state['delivery_point'] = $this->normalizeRow($delivery_point);

        return $this->replaceState($state);
    }

    public function clearDeliveryPoint(): array
    {
        $state = $this->getState();
        $state['delivery_point'] = [];

        return $this->replaceState($state);
    }

    public function clearDeliveryAddress(): array
    {
        $state = $this->getState();
        $state['delivery_address'] = '';

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
            'delivery_method' => Str::lower(Str::squish((string) Arr::get($payload, 'delivery_method', ''))),
            'payment_method' => Str::lower(Str::squish((string) Arr::get($payload, 'payment_method', ''))),
            'city' => $this->normalizeRow((array) Arr::get($payload, 'city', [])),
            'delivery_point' => $this->normalizeRow((array) Arr::get($payload, 'delivery_point', [])),
            'delivery_address' => $this->normalizeDeliveryAddress(Arr::get($payload, 'delivery_address', '')),
            'first_name' => $this->normalizeText($payload, $current_state, 'first_name'),
            'last_name' => $this->normalizeText($payload, $current_state, 'last_name'),
            'phone' => $this->normalizeText($payload, $current_state, 'phone'),
            'email' => $this->normalizeText($payload, $current_state, 'email'),
            'comment' => $this->normalizeText($payload, $current_state, 'comment'),
            'promo_code' => $this->normalizeText($payload, $current_state, 'promo_code'),
            'no_call' => $this->normalizeBoolean($payload, $current_state, 'no_call'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $current_state
     */
    private function normalizeText(array $payload, array $current_state, string $key): string
    {
        $value = Arr::has($payload, $key) ? Arr::get($payload, $key) : Arr::get($current_state, $key, '');

        return Str::squish((string) $value);
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

        return Str::squish((string) $delivery_address);
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
}
