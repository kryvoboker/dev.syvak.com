<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class NovaPoshtaCheckoutStateService
{
    private const SESSION_KEY = 'nova_poshta.checkout_state';

    public function __construct(
        private readonly Request $request,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(): array
    {
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

        $this->request->session()->put(self::SESSION_KEY, $state);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $region
     * @return array<string, mixed>
     */
    public function setRegion(array $region): array
    {
        $state = $this->getState();
        $state['region'] = $this->normalizeRow($region);
        $state['city'] = [];
        $state['delivery_point'] = [];

        return $this->replaceState($state);
    }

    /**
     * @param  array<string, mixed>  $city
     * @return array<string, mixed>
     */
    public function setCity(array $city): array
    {
        $state = $this->getState();
        $state['city'] = $this->normalizeRow($city);
        $state['delivery_point'] = [];

        return $this->replaceState($state);
    }

    /** @return array<string, mixed> */
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

    /** @return array<string, mixed> */
    public function clearDeliveryPoint(): array
    {
        $state = $this->getState();
        $state['delivery_point'] = [];

        return $this->replaceState($state);
    }

    /** @return array<string, mixed> */
    public function clearDeliveryAddress(): array
    {
        $state = $this->getState();
        $state['delivery_address'] = '';

        return $this->replaceState($state);
    }

    public function clear(): void
    {
        $this->request->session()->forget(self::SESSION_KEY);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeState(array $payload): array
    {
        return [
            'delivery_method' => Str::lower(Str::squish((string) Arr::get($payload, 'delivery_method', ''))),
            'region' => $this->normalizeRow((array) Arr::get($payload, 'region', [])),
            'city' => $this->normalizeRow((array) Arr::get($payload, 'city', [])),
            'delivery_point' => $this->normalizeRow((array) Arr::get($payload, 'delivery_point', [])),
            'delivery_address' => $this->normalizeDeliveryAddress(Arr::get($payload, 'delivery_address', '')),
        ];
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

        return collect($row)
            ->only([
                'id',
                'ref',
                'city_ref',
                'description',
                'city_name',
                'region',
                'region_description',
                'city_description',
                'number',
                'site_key',
                'latitude',
                'longitude',
                'schedule',
                'delivery_method',
                'type',
            ])
            ->filter(fn (mixed $value): bool => ! is_null($value) && $value !== '')
            ->all();
    }
}
