<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Tests\Feature;

use Modules\UkrPoshta\Models\UkrPoshtaCity;
use Modules\UkrPoshta\Models\UkrPoshtaDistrict;
use Modules\UkrPoshta\Models\UkrPoshtaPostOffice;
use Modules\UkrPoshta\Models\UkrPoshtaRegion;
use Modules\UkrPoshta\Services\UkrPoshtaCheckoutDataService;
use Modules\UkrPoshta\Support\UkrPoshtaCheckoutStateService;
use Modules\UkrPoshta\Tests\TestCase;

class UkrPoshtaCheckoutDataTest extends TestCase
{
    public function test_it_builds_checkout_payload_from_session_state(): void
    {
        $region = UkrPoshtaRegion::query()->create([
            'region_id' => 10,
            'region_ua' => 'Kyiv region',
        ]);
        $region_id = (int) data_get($region, 'region_id');
        $region_ua = (string) data_get($region, 'region_ua');

        $district = UkrPoshtaDistrict::query()->create([
            'ukr_poshta_region_id' => $region_id,
            'district_id' => 20,
            'region_ua' => $region_ua,
            'district_ua' => 'Bucha district',
        ]);
        $district_id = (int) data_get($district, 'district_id');
        $district_ua = (string) data_get($district, 'district_ua');

        $city = UkrPoshtaCity::query()->create([
            'ukr_poshta_region_id' => $region_id,
            'ukr_poshta_district_id' => $district_id,
            'city_id' => 30,
            'description' => 'Bucha',
            'city_ua' => 'Bucha',
            'latitude' => '50.54',
            'longitude' => '30.21',
            'region_ua' => $region_ua,
            'district_ua' => $district_ua,
        ]);
        $city_id = (int) data_get($city, 'city_id');
        $city_ua = (string) data_get($city, 'city_ua');

        UkrPoshtaPostOffice::query()->create([
            'poregion_id' => $region_id,
            'podistrict_id' => $district_id,
            'pdcity_id' => $city_id,
            'description' => 'Office 1',
            'latitude' => '50.54',
            'longitude' => '30.21',
            'lock_code' => 0,
            'postcode' => 11111,
            'region_ua' => $region_ua,
            'district_ua' => $district_ua,
            'postreet_id' => 'street-1',
        ]);

        $state_service = $this->app->make(UkrPoshtaCheckoutStateService::class);
        $state_service->replaceState([
            'delivery_method' => 'post_office',
            'region' => ['region_id' => 10, 'region_ua' => 'Kyiv region'],
            'district' => ['district_id' => 20, 'district_ua' => 'Bucha district'],
            'city' => ['city_id' => 30, 'city_ua' => 'Bucha'],
            'delivery_point' => ['postcode' => 11111],
        ]);

        $checkout_data = $this->app->make(UkrPoshtaCheckoutDataService::class)->getCheckoutData();
        $module_payload = $this->app->make(UkrPoshtaCheckoutDataService::class)->buildModulePayload('checkout', 'checkout');

        $this->assertTrue($checkout_data['is_prefilled']);
        $this->assertSame('post_office', $checkout_data['delivery_method']);
        $this->assertSame(10, (int) data_get($checkout_data, 'selected_region.region_id'));
        $this->assertSame(20, (int) data_get($checkout_data, 'selected_district.district_id'));
        $this->assertSame(30, (int) data_get($checkout_data, 'selected_city.city_id'));
        $this->assertSame('Bucha', $city_ua);
        $this->assertNotSame('', (string) $module_payload['regions_html']);
        $this->assertNotSame('', (string) $module_payload['districts_html']);
        $this->assertNotSame('', (string) $module_payload['cities_html']);
        $this->assertNotSame('', (string) $module_payload['post_offices_html']);
    }
}
