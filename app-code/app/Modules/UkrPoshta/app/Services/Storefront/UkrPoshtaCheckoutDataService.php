<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Services\Storefront;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\UkrPoshta\Models\UkrPoshtaCity;
use Modules\UkrPoshta\Models\UkrPoshtaDistrict;
use Modules\UkrPoshta\Models\UkrPoshtaPostOffice;
use Modules\UkrPoshta\Models\UkrPoshtaRegion;
use Modules\UkrPoshta\Support\UkrPoshtaCheckoutStateService;

class UkrPoshtaCheckoutDataService
{
    public function __construct(
        private readonly UkrPoshtaCheckoutStateService $checkout_state_service,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getCheckoutData(): array
    {
        $state = $this->checkout_state_service->getState();
        $selected_region_id = $this->toInt(Arr::get($state, 'region.region_id', Arr::get($state, 'region.id', 0)));
        $selected_district_id = $this->toInt(Arr::get($state, 'district.district_id', Arr::get($state, 'district.id', 0)));
        $selected_city_id = $this->toInt(Arr::get($state, 'city.city_id', Arr::get($state, 'city.id', 0)));
        $delivery_method = $this->toString(Arr::get($state, 'delivery_method', ''));
        $has_saved_selection = $selected_region_id > 0 || $selected_district_id > 0 || $selected_city_id > 0 || filled($delivery_method);

        return [
            'state' => $state,
            'delivery_method' => $delivery_method,
            'is_prefilled' => $has_saved_selection,
            'regions' => $this->getRegionRows(),
            'districts' => $selected_region_id > 0 ? $this->getDistrictRows($selected_region_id) : collect(),
            'cities' => $selected_district_id > 0 ? $this->getCityRows($selected_district_id) : collect(),
            'post_offices' => $selected_district_id > 0 ? $this->getPostOfficeRows($selected_district_id, $selected_city_id > 0 ? $selected_city_id : null) : collect(),
            'selected_region' => $this->findSelectedRegion($selected_region_id),
            'selected_district' => $this->findSelectedDistrict($selected_district_id),
            'selected_city' => $this->findSelectedCity($selected_city_id),
            'selected_delivery_point' => Arr::get($state, 'delivery_point', []),
        ];
    }

    /**
     * @return Collection<int, UkrPoshtaRegion>
     */
    public function getRegionRows(): Collection
    {
        return UkrPoshtaRegion::query()
            ->orderBy('region_ua')
            ->get();
    }

    /**
     * @return Collection<int, UkrPoshtaDistrict>
     */
    public function getDistrictRows(?int $region_id = null): Collection
    {
        return UkrPoshtaDistrict::query()
            ->with('ukrPoshtaRegion')
            ->when($region_id > 0, function ($query) use ($region_id): void {
                $query->where('ukr_poshta_region_id', $region_id);
            })
            ->orderBy('district_ua')
            ->get();
    }

    /**
     * @return Collection<int, UkrPoshtaCity>
     */
    public function getCityRows(?int $district_id = null): Collection
    {
        return UkrPoshtaCity::query()
            ->with(['ukrPoshtaRegion', 'ukrPoshtaDistrict'])
            ->when($district_id > 0, function ($query) use ($district_id): void {
                $query->where('ukr_poshta_district_id', $district_id);
            })
            ->orderBy('city_ua')
            ->get();
    }

    /**
     * @return Collection<int, UkrPoshtaPostOffice>
     */
    public function getPostOfficeRows(?int $district_id = null, ?int $city_id = null): Collection
    {
        return UkrPoshtaPostOffice::query()
            ->with(['ukrPoshtaRegion', 'ukrPoshtaDistrict', 'ukrPoshtaCity'])
            ->when($city_id > 0, function ($query) use ($city_id): void {
                $query->where('pdcity_id', $city_id);
            })
            ->when($city_id <= 0 && $district_id > 0, function ($query) use ($district_id): void {
                $query->where('podistrict_id', $district_id);
            })
            ->where('lock_code', 0)
            ->orderBy('postcode')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildModulePayload(string $placement, ?string $page_type = null): array
    {
        $checkout_data = $this->getCheckoutData();

        /** @var Collection<int, UkrPoshtaRegion> $regions */
        $regions = $checkout_data['regions'];
        /** @var Collection<int, UkrPoshtaDistrict> $districts */
        $districts = $checkout_data['districts'];
        /** @var Collection<int, UkrPoshtaCity> $cities */
        $cities = $checkout_data['cities'];
        /** @var Collection<int, UkrPoshtaPostOffice> $post_offices */
        $post_offices = $checkout_data['post_offices'];

        return [
            'placement' => $placement,
            'page_type' => $page_type,
            'state' => $checkout_data['state'],
            'is_prefilled' => (bool) $checkout_data['is_prefilled'],
            'delivery_method' => $this->toString($checkout_data['delivery_method']),
            'selected_region' => $checkout_data['selected_region'],
            'selected_district' => $checkout_data['selected_district'],
            'selected_city' => $checkout_data['selected_city'],
            'selected_delivery_point' => $checkout_data['selected_delivery_point'],
            'regions_html' => $this->renderRegionsHtml($regions),
            'districts_html' => $this->renderDistrictsHtml($districts),
            'cities_html' => $this->renderCitiesHtml($cities),
            'post_offices_html' => $this->renderPostOfficesHtml($post_offices),
        ];
    }

    /**
     * @param  Collection<int, UkrPoshtaRegion>  $rows
     */
    public function renderRegionsHtml(Collection $rows): string
    {
        return view('ukrposhta::storefront.partials.regions', [
            'regions' => $rows,
            'selected_region_id' => $this->toInt(Arr::get($this->checkout_state_service->getState(), 'region.region_id', 0)),
        ])->render();
    }

    /**
     * @param  Collection<int, UkrPoshtaDistrict>  $rows
     */
    public function renderDistrictsHtml(Collection $rows): string
    {
        return view('ukrposhta::storefront.partials.districts', [
            'districts' => $rows,
            'selected_district_id' => $this->toInt(Arr::get($this->checkout_state_service->getState(), 'district.district_id', 0)),
        ])->render();
    }

    /**
     * @param  Collection<int, UkrPoshtaCity>  $rows
     */
    public function renderCitiesHtml(Collection $rows): string
    {
        return view('ukrposhta::storefront.partials.cities', [
            'cities' => $rows,
            'selected_city_id' => $this->toInt(Arr::get($this->checkout_state_service->getState(), 'city.city_id', 0)),
        ])->render();
    }

    /**
     * @param  Collection<int, UkrPoshtaPostOffice>  $rows
     */
    public function renderPostOfficesHtml(Collection $rows): string
    {
        return view('ukrposhta::storefront.partials.post-offices', [
            'post_offices' => $rows,
            'selected_delivery_point_postcode' => $this->toInt(Arr::get($this->checkout_state_service->getState(), 'delivery_point.postcode', 0)),
        ])->render();
    }

    /**
     * @return array<string, mixed>
     */
    private function findSelectedRegion(int $region_id): array
    {
        if ($region_id <= 0) {
            return [];
        }

        return UkrPoshtaRegion::query()
            ->where('region_id', $region_id)
            ->first()
            ?->toArray() ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function findSelectedDistrict(int $district_id): array
    {
        if ($district_id <= 0) {
            return [];
        }

        $district = UkrPoshtaDistrict::query()
            ->with('ukrPoshtaRegion')
            ->where('district_id', $district_id)
            ->first();

        if ($district === null) {
            return [];
        }

        $district_data = $district->toArray();
        $district_data['region'] = $district->ukrPoshtaRegion?->toArray() ?? [];

        return $district_data;
    }

    /**
     * @return array<string, mixed>
     */
    private function findSelectedCity(int $city_id): array
    {
        if ($city_id <= 0) {
            return [];
        }

        $city = UkrPoshtaCity::query()
            ->with(['ukrPoshtaRegion', 'ukrPoshtaDistrict'])
            ->where('city_id', $city_id)
            ->first();

        if ($city === null) {
            return [];
        }

        $city_data = $city->toArray();
        $city_data['region'] = $city->ukrPoshtaRegion?->toArray() ?? [];
        $city_data['district'] = $city->ukrPoshtaDistrict?->toArray() ?? [];

        return $city_data;
    }

    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
