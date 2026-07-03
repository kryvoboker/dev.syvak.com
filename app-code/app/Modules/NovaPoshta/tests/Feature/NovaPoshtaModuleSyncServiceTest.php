<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\NovaPoshta\Models\NovaPoshtaCity;
use Modules\NovaPoshta\Models\NovaPoshtaPoshtomat;
use Modules\NovaPoshta\Models\NovaPoshtaPostOffice;
use Modules\NovaPoshta\Models\NovaPoshtaRegion;
use Modules\NovaPoshta\Services\NovaPoshtaSyncService;
use RuntimeException;
use Tests\TestCase;

class NovaPoshtaModuleSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('cache.default', 'array');

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createGlobalConfigsTable();
        $this->createNovaPoshtaTables();

        DB::table('global_configs')->insert([
            'key' => 'novaposhta.api_key',
            'value' => 'test-api-key',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_sync_all_imports_regions_cities_post_offices_and_poshtomats(): void
    {
        Http::fake(function (HttpRequest $request) {
            $payload = $request->data();
            $called_method = (string) data_get($payload, 'calledMethod');
            $page = (int) data_get($payload, 'methodProperties.Page', 1);

            if ($called_method === 'getSettlementAreas') {
                return Http::response([
                    'success' => true,
                    'data' => [
                        [
                            'Ref' => 'region-1',
                            'AreasCenter' => 'Київ',
                            'Description' => 'Київська область',
                        ],
                        [
                            'Ref' => 'region-2',
                            'AreasCenter' => 'Львів',
                            'Description' => 'Львівська область',
                        ],
                    ],
                ]);
            }

            if ($called_method === 'getSettlements' && $page === 1) {
                return Http::response([
                    'success' => true,
                    'info' => [
                        'totalCount' => 501,
                    ],
                    'data' => [
                        [
                            'Ref' => 'city-1',
                            'Area' => 'region-1',
                            'AreaDescription' => 'Київська',
                            'Description' => 'Київ',
                            'Latitude' => '50.4501',
                            'Longitude' => '30.5234',
                            'CityID' => 101,
                        ],
                    ],
                ]);
            }

            if ($called_method === 'getSettlements' && $page === 2) {
                return Http::response([
                    'success' => true,
                    'info' => [
                        'totalCount' => 501,
                    ],
                    'data' => [
                        [
                            'Ref' => 'city-2',
                            'Area' => 'region-2',
                            'AreaDescription' => 'Львівська',
                            'Description' => 'Львів',
                            'Latitude' => '49.8397',
                            'Longitude' => '24.0297',
                            'CityID' => 102,
                        ],
                    ],
                ]);
            }

            if ($called_method === 'getWarehouses' && $page === 1) {
                return Http::response([
                    'success' => true,
                    'info' => [
                        'totalCount' => 501,
                    ],
                    'data' => [
                        [
                            'Ref' => 'office-1',
                            'SettlementRef' => 'city-1',
                            'Description' => 'Відділення № 12',
                            'Latitude' => '50.4501',
                            'Longitude' => '30.5234',
                            'Schedule' => [
                                'Monday' => '09:00-18:00',
                            ],
                            'CityDescription' => 'Київ',
                            'SiteKey' => 12,
                        ],
                    ],
                ]);
            }

            if ($called_method === 'getWarehouses' && $page === 2) {
                return Http::response([
                    'success' => true,
                    'info' => [
                        'totalCount' => 501,
                    ],
                    'data' => [
                        [
                            'Ref' => 'poshtomat-1',
                            'SettlementRef' => 'city-2',
                            'Description' => 'Поштомат "Нова Пошта" № 7',
                            'Latitude' => '49.8397',
                            'Longitude' => '24.0297',
                            'Schedule' => [
                                'Monday' => '00:00-23:59',
                            ],
                            'CityDescription' => 'Львів',
                            'SiteKey' => 7,
                        ],
                    ],
                ]);
            }

            return Http::response([
                'success' => false,
                'data' => [],
            ], 500);
        });

        $summary = $this->app->make(NovaPoshtaSyncService::class)->syncAll();

        $this->assertSame(2, $summary['regions']['imported']);
        $this->assertSame(2, $summary['cities']['imported']);
        $this->assertSame(1, $summary['post_offices']['imported']);
        $this->assertSame(1, $summary['poshtomats']['imported']);

        $this->assertSame(2, NovaPoshtaRegion::query()->count());
        $this->assertSame(2, NovaPoshtaCity::query()->count());
        $this->assertSame(1, NovaPoshtaPostOffice::query()->count());
        $this->assertSame(1, NovaPoshtaPoshtomat::query()->count());

        $city = NovaPoshtaCity::query()->with('novaPoshtaRegion')->where('ref', 'city-1')->firstOrFail();
        $post_office = NovaPoshtaPostOffice::query()->with('novaPoshtaCity')->where('ref', 'office-1')->firstOrFail();
        $poshtomat = NovaPoshtaPoshtomat::query()->with('novaPoshtaCity')->where('ref', 'poshtomat-1')->firstOrFail();

        $this->assertSame('region-1', (string) data_get($city, 'novaPoshtaRegion.ref'));
        $this->assertSame(12, (int) $post_office->getAttribute('number'));
        $this->assertSame(7, (int) $poshtomat->getAttribute('number'));
        $this->assertSame('city-1', (string) data_get($post_office, 'novaPoshtaCity.ref'));
        $this->assertSame('city-2', (string) data_get($poshtomat, 'novaPoshtaCity.ref'));
    }

    public function test_sync_post_offices_skips_rows_without_matching_city(): void
    {
        Http::fake(function (HttpRequest $request) {
            $payload = $request->data();
            $called_method = (string) data_get($payload, 'calledMethod');

            if ($called_method === 'getWarehouses') {
                return Http::response([
                    'success' => true,
                    'info' => [
                        'totalCount' => 2,
                    ],
                    'data' => [
                        [
                            'Ref' => 'office-0',
                            'SettlementRef' => 'city-1',
                            'Description' => 'Відділення № 11',
                            'Latitude' => '50.4501',
                            'Longitude' => '30.5234',
                            'Schedule' => [
                                'Monday' => '09:00-18:00',
                            ],
                            'CityDescription' => 'Київ',
                            'SiteKey' => 11,
                        ],
                        [
                            'Ref' => 'office-1',
                            'SettlementRef' => 'missing-city',
                            'Description' => 'Відділення № 12',
                            'Latitude' => '50.4501',
                            'Longitude' => '30.5234',
                            'Schedule' => [
                                'Monday' => '09:00-18:00',
                            ],
                            'CityDescription' => 'Київ',
                            'SiteKey' => 12,
                        ],
                    ],
                ]);
            }

            return Http::response([
                'success' => false,
                'data' => [],
            ], 500);
        });

        NovaPoshtaCity::query()->create([
            'nova_poshta_region_id' => NovaPoshtaRegion::query()->create([
                'ref' => 'region-1',
                'regions_center' => 'Київ',
                'description' => 'Київська область',
            ])->getKey(),
            'ref' => 'city-1',
            'region' => 'region-1',
            'description' => 'Київська обл. / Київ',
            'city_name' => 'Київ',
            'latitude' => '50.4501',
            'longitude' => '30.5234',
            'city_id' => 101,
            'region_description' => 'Київська',
        ]);

        $summary = $this->app->make(NovaPoshtaSyncService::class)->syncPostOffices();

        $this->assertSame(1, $summary['imported']);
        $this->assertSame(1, NovaPoshtaPostOffice::query()->count());
    }

    public function test_queued_sync_step_by_step_rebuilds_all_tables_and_persists_summary(): void
    {
        Cache::flush();

        Http::fake(function (HttpRequest $request) {
            $payload = $request->data();
            $called_method = (string) data_get($payload, 'calledMethod');
            $page = (int) data_get($payload, 'methodProperties.Page', 1);

            if ($called_method === 'getSettlementAreas') {
                return Http::response([
                    'success' => true,
                    'data' => [
                        [
                            'Ref' => 'region-1',
                            'AreasCenter' => 'Київ',
                            'Description' => 'Київська область',
                        ],
                    ],
                ]);
            }

            if ($called_method === 'getSettlements' && $page === 1) {
                return Http::response([
                    'success' => true,
                    'info' => [
                        'totalCount' => 501,
                    ],
                    'data' => [
                        [
                            'Ref' => 'city-1',
                            'Area' => 'region-1',
                            'AreaDescription' => 'Київська',
                            'Description' => 'Київ',
                            'Latitude' => '50.4501',
                            'Longitude' => '30.5234',
                            'CityID' => 101,
                        ],
                    ],
                ]);
            }

            if ($called_method === 'getSettlements' && $page === 2) {
                return Http::response([
                    'success' => true,
                    'info' => [
                        'totalCount' => 501,
                    ],
                    'data' => [
                        [
                            'Ref' => 'city-2',
                            'Area' => 'region-1',
                            'AreaDescription' => 'Київська',
                            'Description' => 'Ірпінь',
                            'Latitude' => '50.5191',
                            'Longitude' => '30.2405',
                            'CityID' => 102,
                        ],
                    ],
                ]);
            }

            if ($called_method === 'getWarehouses' && $page === 1) {
                return Http::response([
                    'success' => true,
                    'info' => [
                        'totalCount' => 501,
                    ],
                    'data' => [
                        [
                            'Ref' => 'office-1',
                            'SettlementRef' => 'city-1',
                            'Description' => 'Відділення № 12',
                            'Latitude' => '50.4501',
                            'Longitude' => '30.5234',
                            'Schedule' => [
                                'Monday' => '09:00-18:00',
                            ],
                            'CityDescription' => 'Київ',
                            'SiteKey' => 12,
                        ],
                    ],
                ]);
            }

            if ($called_method === 'getWarehouses' && $page === 2) {
                return Http::response([
                    'success' => true,
                    'info' => [
                        'totalCount' => 501,
                    ],
                    'data' => [
                        [
                            'Ref' => 'poshtomat-1',
                            'SettlementRef' => 'city-2',
                            'Description' => 'Поштомат "Нова Пошта" № 7',
                            'Latitude' => '50.5191',
                            'Longitude' => '30.2405',
                            'Schedule' => [
                                'Monday' => '00:00-23:59',
                            ],
                            'CityDescription' => 'Ірпінь',
                            'SiteKey' => 7,
                        ],
                    ],
                ]);
            }

            return Http::response([
                'success' => false,
                'data' => [],
            ], 500);
        });

        $sync_service = $this->app->make(NovaPoshtaSyncService::class);
        $state = $sync_service->startQueuedSync();

        $this->assertTrue((bool) data_get($state, 'is_running'));
        $this->assertSame('regions', (string) data_get($state, 'stage'));

        for ($i = 0; $i < 10; $i++) {
            $state = $sync_service->processQueuedSyncStep();

            if ((bool) data_get($state, 'is_running') === false) {
                break;
            }
        }

        $this->assertFalse((bool) data_get($state, 'is_running'));
        $this->assertSame('completed', (string) data_get($state, 'stage'));
        $this->assertSame(100, (int) data_get($state, 'overall_progress'));
        $this->assertArrayNotHasKey('buffers', (array) Cache::get('nova_poshta.sync_state', []));
        $this->assertSame(1, NovaPoshtaRegion::query()->count());
        $this->assertSame(2, NovaPoshtaCity::query()->count());
        $this->assertSame(1, NovaPoshtaPostOffice::query()->count());
        $this->assertSame(1, NovaPoshtaPoshtomat::query()->count());
        $this->assertSame('1', (string) data_get(Cache::get('nova_poshta.last_sync_summary', []), 'regions.imported'));
    }

    public function test_queued_sync_can_be_stopped_before_next_batch(): void
    {
        Cache::flush();

        Http::fake(function (HttpRequest $request) {
            $payload = $request->data();
            $called_method = (string) data_get($payload, 'calledMethod');

            if ($called_method === 'getSettlementAreas') {
                return Http::response([
                    'success' => true,
                    'data' => [
                        [
                            'Ref' => 'region-1',
                            'AreasCenter' => 'Київ',
                            'Description' => 'Київська область',
                        ],
                    ],
                ]);
            }

            return Http::response([
                'success' => false,
                'data' => [],
            ], 500);
        });

        $sync_service = $this->app->make(NovaPoshtaSyncService::class);
        $state = $sync_service->startQueuedSync();

        $this->assertTrue((bool) data_get($state, 'is_running'));

        $state = $sync_service->requestQueuedSyncStop();

        $this->assertTrue((bool) data_get($state, 'stop_requested'));

        $state = $sync_service->processQueuedSyncStep();

        $this->assertFalse((bool) data_get($state, 'is_running'));
        $this->assertSame('stopped', (string) data_get($state, 'stage'));
        $this->assertSame('stopped', (string) data_get($state, 'phase'));
        Http::assertNothingSent();
    }

    public function test_queued_sync_stops_immediately_on_critical_error(): void
    {
        Cache::flush();

        Http::fake(function (HttpRequest $request) {
            $payload = $request->data();
            $called_method = (string) data_get($payload, 'calledMethod');
            $page = (int) data_get($payload, 'methodProperties.Page', 1);

            if ($called_method === 'getSettlementAreas') {
                return Http::response([
                    'success' => true,
                    'data' => [
                        [
                            'Ref' => 'region-1',
                            'AreasCenter' => 'Київ',
                            'Description' => 'Київська область',
                        ],
                    ],
                ]);
            }

            if ($called_method === 'getSettlements' && $page === 1) {
                return Http::response([
                    'success' => false,
                    'data' => [],
                ], 500);
            }

            return Http::response([
                'success' => false,
                'data' => [],
            ], 500);
        });

        $sync_service = $this->app->make(NovaPoshtaSyncService::class);
        $sync_service->startQueuedSync();
        $sync_service->processQueuedSyncStep();
        $state = $sync_service->processQueuedSyncStep();

        $this->assertFalse((bool) data_get($state, 'is_running'));
        $this->assertSame('failed', (string) data_get($state, 'stage'));
        $this->assertSame('failed', (string) data_get($state, 'phase'));
        $this->assertNotEmpty((string) data_get($state, 'message'));
    }

    public function test_sync_regions_aborts_when_api_returns_empty_payload(): void
    {
        NovaPoshtaRegion::query()->create([
            'ref' => 'legacy-region',
            'regions_center' => 'Legacy',
            'description' => 'Legacy region',
        ]);

        Http::fake(function (HttpRequest $request) {
            $called_method = (string) data_get($request->data(), 'calledMethod');

            if ($called_method === 'getSettlementAreas') {
                return Http::response([
                    'success' => true,
                    'data' => [],
                ]);
            }

            return Http::response([
                'success' => false,
                'data' => [],
            ], 500);
        });

        try {
            $this->app->make(NovaPoshtaSyncService::class)->syncRegions();
            $this->fail('The sync should have failed before clearing the table.');
        } catch (RuntimeException) {
            $this->assertSame(1, NovaPoshtaRegion::query()->count());
        }
    }

    private function createNovaPoshtaTables(): void
    {
        Schema::create('nova_poshta_regions', function (Blueprint $table): void {
            $table->id();
            $table->string('ref')->nullable();
            $table->string('regions_center')->nullable();
            $table->string('description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('nova_poshta_cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nova_poshta_region_id')->constrained('nova_poshta_regions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('ref')->nullable()->index();
            $table->string('region')->nullable()->index();
            $table->string('description', 500)->nullable();
            $table->string('city_name')->nullable()->index();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('region_description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('nova_poshta_post_offices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nova_poshta_city_id')->constrained('nova_poshta_cities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('ref')->nullable()->index();
            $table->string('city_ref')->nullable()->index();
            $table->string('description', 500)->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->tinyText('schedule')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->string('city_description', 500)->nullable();
            $table->unsignedBigInteger('site_key')->nullable();
            $table->timestamps();
        });

        Schema::create('nova_poshta_poshtomats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nova_poshta_city_id')->constrained('nova_poshta_cities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('ref')->nullable()->index();
            $table->string('city_ref')->nullable()->index();
            $table->string('description', 500)->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->tinyText('schedule')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->string('city_description', 500)->nullable();
            $table->unsignedBigInteger('site_key')->nullable();
            $table->timestamps();
        });
    }

    private function createGlobalConfigsTable(): void
    {
        Schema::create('global_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}
