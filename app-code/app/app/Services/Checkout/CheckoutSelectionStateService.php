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
            'city' => $this->normalizeRow((array) Arr::get($payload, 'city', [])),
            'delivery_point' => $this->normalizeRow((array) Arr::get($payload, 'delivery_point', [])),
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

        return Collection::make($row)
            ->only([
                'city_description',
                'nova_poshta_city_id',
                'ukr_poshta_city_id',
                'city_lat',
                'city_lng',
                'description',
                'ref',
                'city_name',
                'city_ua',
                'latitude',
                'longitude',
            ])
            ->filter(fn (mixed $value): bool => ! is_null($value) && $value !== '')
            ->all();
    }
}
