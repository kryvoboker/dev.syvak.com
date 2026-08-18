<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UkrPoshtaCheckoutStateService
{
    private const string SESSION_KEY = 'ukr_poshta.checkout_state';

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

        if (! is_array($state)) {
            return [];
        }

        /** @var array<string, mixed> $state */
        return $state;
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
        $state['district'] = [];
        $state['city'] = [];
        $state['delivery_point'] = [];

        return $this->replaceState($state);
    }

    /**
     * @param  array<string, mixed>  $district
     * @return array<string, mixed>
     */
    public function setDistrict(array $district): array
    {
        $state = $this->getState();
        $state['district'] = $this->normalizeRow($district);
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
            'delivery_method' => Str::lower(Str::squish($this->stringValue(Arr::get($payload, 'delivery_method', '')))),
            'region' => $this->normalizeRow($this->stringKeyedArray(Arr::get($payload, 'region', []))),
            'district' => $this->normalizeRow($this->stringKeyedArray(Arr::get($payload, 'district', []))),
            'city' => $this->normalizeRow($this->stringKeyedArray(Arr::get($payload, 'city', []))),
            'delivery_point' => $this->normalizeRow($this->stringKeyedArray(Arr::get($payload, 'delivery_point', []))),
        ];
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
                'region_id',
                'district_id',
                'city_id',
                'poregion_id',
                'podistrict_id',
                'pdcity_id',
                'postcode',
                'description',
                'city_ua',
                'region_ua',
                'district_ua',
                'latitude',
                'longitude',
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
