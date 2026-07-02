<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\UkrPoshta\Models\UkrPoshtaCity;
use Modules\UkrPoshta\Models\UkrPoshtaDistrict;
use Modules\UkrPoshta\Models\UkrPoshtaPostOffice;
use Modules\UkrPoshta\Models\UkrPoshtaRegion;
use Modules\UkrPoshta\Services\UkrPoshtaSyncService;
use Modules\UkrPoshta\Support\UkrPoshtaConfig;
use Modules\UkrPoshta\Tests\TestCase;

class UkrPoshtaSyncServiceTest extends TestCase
{
    public function test_it_processes_chunked_sync_flow(): void
    {
        set_global_config([
            UkrPoshtaConfig::API_KEY_GLOBAL_CONFIG_KEY => ['value' => 'up-test-key', 'is_active' => true],
        ]);

        Http::fake([
            '*get_regions_by_region_ua*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['REGION_ID' => 1, 'REGION_UA' => 'Region 1'],
                        ['REGION_ID' => 2, 'REGION_UA' => 'Region 2'],
                    ],
                ],
            ]),
            '*get_districts_by_region_id_and_district_ua*region_id=1*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['DISTRICT_ID' => 11, 'REGION_ID' => 1, 'REGION_UA' => 'Region 1', 'DISTRICT_UA' => 'District 11'],
                    ],
                ],
            ]),
            '*get_districts_by_region_id_and_district_ua*region_id=2*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['DISTRICT_ID' => 12, 'REGION_ID' => 2, 'REGION_UA' => 'Region 2', 'DISTRICT_UA' => 'District 12'],
                    ],
                ],
            ]),
            '*get_city_by_region_id_and_district_id_and_city_ua*district_id=11*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['CITY_ID' => 111, 'REGION_ID' => 1, 'DISTRICT_ID' => 11, 'CITY_UA' => 'City 111', 'REGION_UA' => 'Region 1', 'DISTRICT_UA' => 'District 11', 'LATTITUDE' => '50.10', 'LONGITUDE' => '30.10'],
                    ],
                ],
            ]),
            '*get_city_by_region_id_and_district_id_and_city_ua*district_id=12*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['CITY_ID' => 112, 'REGION_ID' => 2, 'DISTRICT_ID' => 12, 'CITY_UA' => 'City 112', 'REGION_UA' => 'Region 2', 'DISTRICT_UA' => 'District 12', 'LATTITUDE' => '50.11', 'LONGITUDE' => '30.11'],
                    ],
                ],
            ]),
            '*get_postoffices_by_postindex*pdDistrictId=11*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['PDCITY_ID' => 111, 'POREGION_ID' => 1, 'PODISTRICT_ID' => 11, 'PDCITY_UA' => 'City 111', 'PDCITYTYPE_UA' => 'м.', 'ADDRESS' => 'Main street', 'POSTCODE' => 11111, 'REGION_UA' => 'Region 1', 'DISTRICT_UA' => 'District 11', 'POSTREET_ID' => 'street-111', 'LOCK_CODE' => 0, 'LATTITUDE' => '50.10', 'LONGITUDE' => '30.10'],
                    ],
                ],
            ]),
            '*get_postoffices_by_postindex*pdDistrictId=12*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['PDCITY_ID' => 112, 'POREGION_ID' => 2, 'PODISTRICT_ID' => 12, 'PDCITY_UA' => 'City 112', 'PDCITYTYPE_UA' => 'м.', 'ADDRESS' => 'Second street', 'POSTCODE' => 22222, 'REGION_UA' => 'Region 2', 'DISTRICT_UA' => 'District 12', 'POSTREET_ID' => 'street-112', 'LOCK_CODE' => 0, 'LATTITUDE' => '50.11', 'LONGITUDE' => '30.11'],
                    ],
                ],
            ]),
        ]);

        $summary = $this->app->make(UkrPoshtaSyncService::class)->syncAll();

        $this->assertSame(2, $summary['regions']['imported']);
        $this->assertSame(2, $summary['districts']['imported']);
        $this->assertSame(2, $summary['cities']['imported']);
        $this->assertSame(2, $summary['post_offices']['imported']);

        $this->assertSame(2, UkrPoshtaRegion::query()->count());
        $this->assertSame(2, UkrPoshtaDistrict::query()->count());
        $this->assertSame(2, UkrPoshtaCity::query()->count());
        $this->assertSame(2, UkrPoshtaPostOffice::query()->count());
    }

    public function test_it_tracks_stage_summaries_and_transitions_by_stage(): void
    {
        set_global_config([
            UkrPoshtaConfig::API_KEY_GLOBAL_CONFIG_KEY => ['value' => 'up-test-key', 'is_active' => true],
        ]);

        Http::fake([
            '*get_regions_by_region_ua*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['REGION_ID' => 1, 'REGION_UA' => 'Region 1'],
                        ['REGION_ID' => 2, 'REGION_UA' => 'Region 2'],
                    ],
                ],
            ]),
            '*get_districts_by_region_id_and_district_ua*region_id=1*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['DISTRICT_ID' => 11, 'REGION_ID' => 1, 'REGION_UA' => 'Region 1', 'DISTRICT_UA' => 'District 11'],
                    ],
                ],
            ]),
            '*get_districts_by_region_id_and_district_ua*region_id=2*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['DISTRICT_ID' => 12, 'REGION_ID' => 2, 'REGION_UA' => 'Region 2', 'DISTRICT_UA' => 'District 12'],
                    ],
                ],
            ]),
            '*get_city_by_region_id_and_district_id_and_city_ua*district_id=11*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['CITY_ID' => 111, 'REGION_ID' => 1, 'DISTRICT_ID' => 11, 'CITY_UA' => 'City 111', 'REGION_UA' => 'Region 1', 'DISTRICT_UA' => 'District 11'],
                    ],
                ],
            ]),
            '*get_city_by_region_id_and_district_id_and_city_ua*district_id=12*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['CITY_ID' => 112, 'REGION_ID' => 2, 'DISTRICT_ID' => 12, 'CITY_UA' => 'City 112', 'REGION_UA' => 'Region 2', 'DISTRICT_UA' => 'District 12'],
                    ],
                ],
            ]),
            '*get_postoffices_by_postindex*pdDistrictId=11*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['PDCITY_ID' => 111, 'POREGION_ID' => 1, 'PODISTRICT_ID' => 11, 'PDCITY_UA' => 'City 111', 'PDCITYTYPE_UA' => 'м.', 'ADDRESS' => 'Main street', 'POSTCODE' => 11111, 'REGION_UA' => 'Region 1', 'DISTRICT_UA' => 'District 11', 'POSTREET_ID' => 'street-111', 'LOCK_CODE' => 0],
                    ],
                ],
            ]),
            '*get_postoffices_by_postindex*pdDistrictId=12*' => Http::response([
                'Entries' => [
                    'Entry' => [
                        ['PDCITY_ID' => 112, 'POREGION_ID' => 2, 'PODISTRICT_ID' => 12, 'PDCITY_UA' => 'City 112', 'PDCITYTYPE_UA' => 'м.', 'ADDRESS' => 'Second street', 'POSTCODE' => 22222, 'REGION_UA' => 'Region 2', 'DISTRICT_UA' => 'District 12', 'POSTREET_ID' => 'street-112', 'LOCK_CODE' => 0],
                    ],
                ],
            ]),
        ]);

        $sync_service = $this->app->make(UkrPoshtaSyncService::class);

        $state = $sync_service->startQueuedSync();
        $this->assertSame('regions', $state['stage']);

        $state = $sync_service->processQueuedSyncStep();
        $this->assertSame('districts', $state['stage']);
        $this->assertSame(2, (int) data_get($state, 'summary.regions.imported', 0));

        $state = $sync_service->processQueuedSyncStep();
        $this->assertSame('districts', $state['stage']);
        $this->assertSame(2, (int) data_get($state, 'summary.regions.imported', 0));
        $this->assertSame(1, (int) data_get($state, 'summary.districts.imported', 0));

        $state = $sync_service->processQueuedSyncStep();
        $this->assertSame('cities', $state['stage']);
        $this->assertSame(2, (int) data_get($state, 'summary.districts.imported', 0));

        $state = $sync_service->processQueuedSyncStep();
        $this->assertSame('cities', $state['stage']);
        $this->assertSame(2, (int) data_get($state, 'summary.districts.imported', 0));
        $this->assertSame(1, (int) data_get($state, 'summary.cities.imported', 0));
    }

    public function test_it_stops_queue_processing_after_critical_batch_failure(): void
    {
        set_global_config([
            UkrPoshtaConfig::API_KEY_GLOBAL_CONFIG_KEY => ['value' => 'up-test-key', 'is_active' => true],
        ]);

        Http::fake(function (Request $request): mixed {
            $url = $request->url();

            if (str_contains($url, 'get_regions_by_region_ua')) {
                return Http::response([
                    'Entries' => [
                        'Entry' => [
                            ['REGION_ID' => 1, 'REGION_UA' => 'Region 1'],
                        ],
                    ],
                ]);
            }

            if (str_contains($url, 'get_districts_by_region_id_and_district_ua')) {
                throw new \RuntimeException('Critical Ukr Poshta districts failure');
            }

            return Http::response(['Entries' => ['Entry' => []]]);
        });

        $sync_service = $this->app->make(UkrPoshtaSyncService::class);

        $sync_service->startQueuedSync();
        $sync_service->processQueuedSyncStep();
        $state = $sync_service->processQueuedSyncStep();

        $this->assertSame('failed', $state['stage']);
        $this->assertFalse((bool) $state['is_running']);
        $this->assertStringContainsString('Critical Ukr Poshta districts failure', (string) $state['message']);
    }
}
