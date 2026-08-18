<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Services\Storefront;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\NovaPoshta\Models\NovaPoshtaCity;
use Modules\NovaPoshta\Models\NovaPoshtaPoshtomat;
use Modules\NovaPoshta\Models\NovaPoshtaPostOffice;
use Modules\NovaPoshta\Models\NovaPoshtaRegion;
use Modules\NovaPoshta\Support\NovaPoshtaCheckoutStateService;

class NovaPoshtaCheckoutDataService
{
    public function __construct(
        private readonly NovaPoshtaCheckoutStateService $checkout_state_service,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getCheckoutData(): array
    {
        $state = $this->checkout_state_service->getState();
        $selected_region_ref = $this->toString(Arr::get($state, 'region.ref', ''));
        $selected_city_ref = $this->toString(Arr::get($state, 'city.ref', ''));
        $delivery_method = $this->toString(Arr::get($state, 'delivery_method', ''));
        $has_saved_selection = filled($selected_region_ref) || filled($selected_city_ref) || filled($delivery_method);

        /** @var Collection<int, NovaPoshtaRegion> $regions */
        $regions = $has_saved_selection ? $this->getRegionRows() : new Collection();
        /** @var Collection<int, NovaPoshtaCity> $cities */
        $cities = filled($selected_region_ref) ? $this->getCityRows($selected_region_ref) : new Collection();
        /** @var Collection<int, NovaPoshtaPostOffice> $post_offices */
        $post_offices = filled($selected_city_ref) && $delivery_method === 'post_office' ? $this->getPostOfficeRows($selected_city_ref) : new Collection();
        /** @var Collection<int, NovaPoshtaPoshtomat> $poshtomats */
        $poshtomats = filled($selected_city_ref) && $delivery_method === 'poshtomat' ? $this->getPoshtomatRows($selected_city_ref) : new Collection();

        return [
            'state' => $state,
            'delivery_method' => $delivery_method,
            'is_prefilled' => $has_saved_selection,
            'regions' => $regions,
            'cities' => $cities,
            'post_offices' => $post_offices,
            'poshtomats' => $poshtomats,
            'selected_region' => $this->findSelectedRegion($selected_region_ref),
            'selected_city' => $this->findSelectedCity($selected_city_ref),
            'selected_delivery_point' => Arr::get($state, 'delivery_point', []),
        ];
    }

    /**
     * @return Collection<int, NovaPoshtaRegion>
     */
    public function getRegionRows(): Collection
    {
        return NovaPoshtaRegion::query()
            ->orderBy('description')
            ->get();
    }

    /**
     * @return Collection<int, NovaPoshtaCity>
     */
    public function getCityRows(?string $region_ref = null): Collection
    {
        return NovaPoshtaCity::query()
            ->with('novaPoshtaRegion')
            ->when(filled($region_ref), function ($query) use ($region_ref): void {
                $query->whereHas('novaPoshtaRegion', function ($relation_query) use ($region_ref): void {
                    $relation_query->where('ref', $region_ref);
                });
            })
            ->orderBy('description')
            ->get();
    }

    /**
     * @return Collection<int, NovaPoshtaPostOffice>
     */
    public function getPostOfficeRows(?string $city_ref = null): Collection
    {
        return NovaPoshtaPostOffice::query()
            ->with('novaPoshtaCity')
            ->when(filled($city_ref), function ($query) use ($city_ref): void {
                $query->where('city_ref', $city_ref);
            })
            ->orderBy('description')
            ->get();
    }

    /**
     * @return Collection<int, NovaPoshtaPoshtomat>
     */
    public function getPoshtomatRows(?string $city_ref = null): Collection
    {
        return NovaPoshtaPoshtomat::query()
            ->with('novaPoshtaCity')
            ->when(filled($city_ref), function ($query) use ($city_ref): void {
                $query->where('city_ref', $city_ref);
            })
            ->orderBy('description')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildModulePayload(string $placement, ?string $page_type = null): array
    {
        $checkout_data = $this->getCheckoutData();

        /** @var Collection<int, NovaPoshtaRegion> $regions */
        $regions = $checkout_data['regions'];
        /** @var Collection<int, NovaPoshtaCity> $cities */
        $cities = $checkout_data['cities'];
        /** @var Collection<int, NovaPoshtaPostOffice> $post_offices */
        $post_offices = $checkout_data['post_offices'];
        /** @var Collection<int, NovaPoshtaPoshtomat> $poshtomats */
        $poshtomats = $checkout_data['poshtomats'];

        return [
            'placement' => $placement,
            'page_type' => $page_type,
            'state' => $checkout_data['state'],
            'is_prefilled' => (bool) $checkout_data['is_prefilled'],
            'delivery_method' => $this->toString($checkout_data['delivery_method']),
            'selected_region' => $checkout_data['selected_region'],
            'selected_city' => $checkout_data['selected_city'],
            'selected_delivery_point' => $checkout_data['selected_delivery_point'],
            'regions_html' => $this->renderRegionsHtml($regions),
            'cities_html' => $this->renderCitiesHtml($cities),
            'post_offices_html' => $this->renderPostOfficesHtml($post_offices),
            'poshtomats_html' => $this->renderPoshtomatsHtml($poshtomats),
        ];
    }

    /**
     * @param  Collection<int, NovaPoshtaRegion>  $rows
     */
    public function renderRegionsHtml(Collection $rows): string
    {
        return view('novaposhta::storefront.partials.regions', [
            'regions' => $rows,
            'selected_region_ref' => $this->toString(Arr::get($this->checkout_state_service->getState(), 'region.ref', '')),
        ])->render();
    }

    /**
     * @param  Collection<int, NovaPoshtaCity>  $rows
     */
    public function renderCitiesHtml(Collection $rows): string
    {
        return view('novaposhta::storefront.partials.cities', [
            'cities' => $rows,
            'selected_city_ref' => $this->toString(Arr::get($this->checkout_state_service->getState(), 'city.ref', '')),
        ])->render();
    }

    /**
     * @param  Collection<int, NovaPoshtaPostOffice>  $rows
     */
    public function renderPostOfficesHtml(Collection $rows): string
    {
        return view('novaposhta::storefront.partials.post-offices', [
            'post_offices' => $rows,
            'selected_delivery_point_ref' => $this->toString(Arr::get($this->checkout_state_service->getState(), 'delivery_point.ref', '')),
        ])->render();
    }

    /**
     * @param  Collection<int, NovaPoshtaPoshtomat>  $rows
     */
    public function renderPoshtomatsHtml(Collection $rows): string
    {
        return view('novaposhta::storefront.partials.poshtomats', [
            'poshtomats' => $rows,
            'selected_delivery_point_ref' => $this->toString(Arr::get($this->checkout_state_service->getState(), 'delivery_point.ref', '')),
        ])->render();
    }

    /**
     * @return array<string, mixed>
     */
    private function findSelectedRegion(string $region_ref): array
    {
        if (blank($region_ref)) {
            return [];
        }

        $region = NovaPoshtaRegion::query()
            ->where('ref', $region_ref)
            ->first();

        return $region?->toArray() ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function findSelectedCity(string $city_ref): array
    {
        if (blank($city_ref)) {
            return [];
        }

        $city = NovaPoshtaCity::query()
            ->with('novaPoshtaRegion')
            ->where('ref', $city_ref)
            ->first();

        if ($city === null) {
            return [];
        }

        $city_data = $city->toArray();
        $city_data['region'] = $city->novaPoshtaRegion?->toArray() ?? [];

        return $city_data;
    }

    private function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
