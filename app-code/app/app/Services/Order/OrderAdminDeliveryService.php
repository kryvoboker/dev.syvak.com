<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Order\DeliveryMethodEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Modules\NovaPoshta\Models\NovaPoshtaCity;
use Modules\NovaPoshta\Models\NovaPoshtaPoshtomat;
use Modules\NovaPoshta\Models\NovaPoshtaPostOffice;
use Modules\NovaPoshta\Support\NovaPoshtaConfig;
use Modules\UkrPoshta\Models\UkrPoshtaCity;
use Modules\UkrPoshta\Models\UkrPoshtaPostOffice;
use Modules\UkrPoshta\Support\UkrPoshtaConfig;

final class OrderAdminDeliveryService
{
    public const int SEARCH_LIMIT = 100;

    public const int MIN_SEARCH_LENGTH = 3;

    public function __construct(
        private readonly NovaPoshtaConfig $nova_poshta_config,
        private readonly UkrPoshtaConfig $ukr_poshta_config,
    ) {
    }

    public function getDefaultDeliveryCost(?string $method): float
    {
        return match ($method) {
            DeliveryMethodEnum::NovaPoshta->value, DeliveryMethodEnum::NovaPoshtaCourier->value, DeliveryMethodEnum::NovaPoshtaPoshtomat->value => $this->nova_poshta_config->isDeliveryCostEnabled()
                ? $this->floatValue($this->nova_poshta_config->getDeliveryCost())
                : 0.0,
            DeliveryMethodEnum::UkrPoshta->value => $this->ukr_poshta_config->isDeliveryCostEnabled()
                ? $this->floatValue($this->ukr_poshta_config->getDeliveryCost())
                : 0.0,
            default => 0.0,
        };
    }

    /**
     * @return array{city: bool, delivery_point: bool, courier_address: bool, postcode: bool, cost: bool}
     */
    public function getCapabilities(?string $method): array
    {
        return match ($method) {
            DeliveryMethodEnum::NovaPoshta->value, DeliveryMethodEnum::NovaPoshtaPoshtomat->value, DeliveryMethodEnum::UkrPoshta->value => [
                'city' => true,
                'delivery_point' => true,
                'courier_address' => false,
                'postcode' => true,
                'cost' => true,
            ],
            DeliveryMethodEnum::NovaPoshtaCourier->value => [
                'city' => true,
                'delivery_point' => false,
                'courier_address' => true,
                'postcode' => false,
                'cost' => true,
            ],
            DeliveryMethodEnum::PickupStore->value => [
                'city' => false,
                'delivery_point' => false,
                'courier_address' => false,
                'postcode' => false,
                'cost' => false,
            ],
            default => [
                'city' => false,
                'delivery_point' => false,
                'courier_address' => false,
                'postcode' => false,
                'cost' => true,
            ],
        };
    }

    /** @return array<string, string> */
    public function searchCities(string $method, string $search): array
    {
        $search = Str::trim($search);

        if (Str::length($search) < self::MIN_SEARCH_LENGTH || ! $this->getCapabilities($method)['city']) {
            return [];
        }

        return match ($method) {
            DeliveryMethodEnum::NovaPoshta->value, DeliveryMethodEnum::NovaPoshtaCourier->value, DeliveryMethodEnum::NovaPoshtaPoshtomat->value => NovaPoshtaCity::query()
                ->where(function (Builder $query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('city_name', 'like', "%{$search}%")
                        ->orWhere('ref', 'like', "%{$search}%");
                })
                ->orderBy('description')->limit(self::SEARCH_LIMIT)->get()->toBase()
                ->mapWithKeys(fn (NovaPoshtaCity $city): array => [
                    $this->stringValue($city->getAttribute('ref')) => $this->cityLabel($this->stringValue($city->getAttribute('description') ?: $city->getAttribute('city_name'))),
                ])->all(),
            DeliveryMethodEnum::UkrPoshta->value => UkrPoshtaCity::query()
                ->where(function (Builder $query) use ($search): void {
                    $query->where('city_ua', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('city_id', 'like', "%{$search}%");
                })
                ->orderBy('city_ua')->limit(self::SEARCH_LIMIT)->get()->toBase()
                ->mapWithKeys(fn (UkrPoshtaCity $city): array => [
                    $this->stringValue($city->getAttribute('city_id')) => $this->cityLabel($this->stringValue($city->getAttribute('city_ua') ?: $city->getAttribute('description'))),
                ])->all(),
            default => [],
        };
    }

    /** @return array<string, string> */
    public function searchDeliveryPoints(string $method, string $city_id, string $search): array
    {
        $search = Str::trim($search);

        if (Str::length($search) < self::MIN_SEARCH_LENGTH || $city_id === '' || ! $this->getCapabilities($method)['delivery_point']) {
            return [];
        }

        return match ($method) {
            DeliveryMethodEnum::NovaPoshta->value => $this->searchNovaPoshtaPoints(NovaPoshtaPostOffice::class, $city_id, $search),
            DeliveryMethodEnum::NovaPoshtaPoshtomat->value => $this->searchNovaPoshtaPoints(NovaPoshtaPoshtomat::class, $city_id, $search),
            DeliveryMethodEnum::UkrPoshta->value => UkrPoshtaPostOffice::query()
                ->where('pdcity_id', (int) $city_id)->where('lock_code', 0)
                ->where(function (Builder $query) use ($search): void {
                    $query->where('postcode', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->orderBy('postcode')->limit(self::SEARCH_LIMIT)->get()->toBase()
                ->mapWithKeys(fn (UkrPoshtaPostOffice $point): array => [
                    $this->stringValue($point->getAttribute('postcode')) => $this->pointLabel($this->stringValue($point->getAttribute('description')), $this->stringValue($point->getAttribute('postcode'))),
                ])->all(),
            default => [],
        };
    }

    /** @return array<string, mixed>|null */
    public function findCity(string $method, string $city_id): ?array
    {
        if ($city_id === '') {
            return null;
        }

        $city = match ($method) {
            DeliveryMethodEnum::NovaPoshta->value, DeliveryMethodEnum::NovaPoshtaCourier->value, DeliveryMethodEnum::NovaPoshtaPoshtomat->value => NovaPoshtaCity::query()->where('ref', $city_id)->first(),
            DeliveryMethodEnum::UkrPoshta->value => UkrPoshtaCity::query()->where('city_id', $city_id)->first(),
            default => null,
        };

        if ($city instanceof NovaPoshtaCity) {
            return ['id' => $this->stringValue($city->getAttribute('ref')), 'name' => $this->stringValue($city->getAttribute('description') ?: $city->getAttribute('city_name')), 'provider_data' => $city->toArray()];
        }

        if ($city instanceof UkrPoshtaCity) {
            return ['id' => $this->stringValue($city->getAttribute('city_id')), 'name' => $this->stringValue($city->getAttribute('city_ua') ?: $city->getAttribute('description')), 'provider_data' => $city->toArray()];
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public function findDeliveryPoint(string $method, string $city_id, string $point_id): ?array
    {
        if ($city_id === '' || $point_id === '') {
            return null;
        }

        $point = match ($method) {
            DeliveryMethodEnum::NovaPoshta->value => NovaPoshtaPostOffice::query()->where('city_ref', $city_id)->where('ref', $point_id)->first(),
            DeliveryMethodEnum::NovaPoshtaPoshtomat->value => NovaPoshtaPoshtomat::query()->where('city_ref', $city_id)->where('ref', $point_id)->first(),
            DeliveryMethodEnum::UkrPoshta->value => UkrPoshtaPostOffice::query()->where('pdcity_id', $city_id)->where('postcode', $point_id)->where('lock_code', 0)->first(),
            default => null,
        };

        if (! $point instanceof NovaPoshtaPostOffice && ! $point instanceof NovaPoshtaPoshtomat && ! $point instanceof UkrPoshtaPostOffice) {
            return null;
        }

        return [
            'id' => $point instanceof UkrPoshtaPostOffice ? $this->stringValue($point->getAttribute('postcode')) : $this->stringValue($point->getAttribute('ref')),
            'name' => $this->stringValue($point->getAttribute('description')),
            'postcode' => $point instanceof UkrPoshtaPostOffice ? $this->stringValue($point->getAttribute('postcode')) : null,
            'provider_data' => $point->toArray(),
        ];
    }

    /** @param class-string<NovaPoshtaPostOffice|NovaPoshtaPoshtomat> $model */
    /** @return array<string, string> */
    private function searchNovaPoshtaPoints(string $model, string $city_id, string $search): array
    {
        $query = $model::query();
        /** @var Builder<NovaPoshtaPostOffice|NovaPoshtaPoshtomat> $query */

        return $query->where('city_ref', $city_id)
            ->where(function (Builder $query) use ($search): void {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%")
                    ->orWhere('ref', 'like', "%{$search}%");
            })
            ->orderBy('number')->limit(self::SEARCH_LIMIT)->get()->toBase()
            ->mapWithKeys(fn (NovaPoshtaPostOffice|NovaPoshtaPoshtomat $point): array => [
                $this->stringValue($point->getAttribute('ref')) => $this->pointLabel($this->stringValue($point->getAttribute('description')), $this->stringValue($point->getAttribute('number'))),
            ])->all();
    }

    private function cityLabel(string $name): string
    {
        return $name !== '' ? $name : 'Unknown city';
    }

    private function pointLabel(string $name, ?string $secondary = null): string
    {
        return collect([$name, $secondary !== null && $secondary !== '0' ? $secondary : null])->filter()->implode(' — ');
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function floatValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
