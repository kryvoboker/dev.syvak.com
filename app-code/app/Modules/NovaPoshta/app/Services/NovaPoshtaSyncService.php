<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Services;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\NovaPoshta\Models\NovaPoshtaCity;
use Modules\NovaPoshta\Models\NovaPoshtaPoshtomat;
use Modules\NovaPoshta\Models\NovaPoshtaPostOffice;
use Modules\NovaPoshta\Models\NovaPoshtaRegion;
use Modules\NovaPoshta\Support\NovaPoshtaConfig;
use RuntimeException;
use Throwable;

class NovaPoshtaSyncService
{
    private const string SYNC_STATE_CACHE_KEY = 'nova_poshta.sync_state';

    private const string LAST_SYNC_SUMMARY_CACHE_KEY = 'nova_poshta.last_sync_summary';

    private const string STAGE_REGIONS = 'regions';

    private const string STAGE_CITIES = 'cities';

    private const string STAGE_POST_OFFICES = 'post_offices';

    private const string STAGE_POSHTOMATS = 'poshtomats';

    private const string PHASE_COLLECT = 'collect';

    public function __construct(
        private readonly NovaPoshtaApiService $api_service,
        private readonly NovaPoshtaConfig $config,
    ) {
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function syncAll(): array
    {
        try {
            return [
                'regions' => $this->syncRegions(),
                'cities' => $this->syncCities(),
                'post_offices' => $this->syncPostOffices(),
                'poshtomats' => $this->syncPoshtomats(),
            ];
        } catch (Throwable $throwable) {
            throw new RuntimeException('Nova Poshta sync failed.', previous: $throwable);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function startQueuedSync(): array
    {
        $current_state = $this->getQueuedSyncState();

        if (Arr::get($current_state, 'is_running', false) === true) {
            throw new RuntimeException('Nova Poshta sync is already running.');
        }

        $state = $this->makeInitialQueuedSyncState();
        $state['is_running'] = true;
        $state['stage'] = self::STAGE_REGIONS;
        $state['phase'] = self::PHASE_COLLECT;
        $state['started_at'] = now()->toIso8601String();
        $state['updated_at'] = now()->toIso8601String();
        $state['message'] = __('admin/modules/nova_poshta.sync.messages.started');

        Cache::put(self::SYNC_STATE_CACHE_KEY, $state, now()->addDay());

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueuedSyncState(): array
    {
        $state = Cache::get(self::SYNC_STATE_CACHE_KEY, $this->makeInitialQueuedSyncState());

        return is_array($state) ? $state : $this->makeInitialQueuedSyncState();
    }

    /**
     * @return array<string, mixed>
     */
    public function processQueuedSyncStep(): array
    {
        $state = $this->getQueuedSyncState();

        if (Arr::get($state, 'is_running', false) !== true) {
            return $state;
        }

        $stage = (string) Arr::get($state, 'stage', '');

        return match ($stage) {
            self::STAGE_REGIONS => $this->processRegionsStage($state),
            self::STAGE_CITIES => $this->processCitiesStage($state),
            self::STAGE_POST_OFFICES => $this->processWarehousesStage($state, NovaPoshtaPostOffice::class, false),
            self::STAGE_POSHTOMATS => $this->processWarehousesStage($state, NovaPoshtaPoshtomat::class, true),
            'completed', 'failed' => $state,
            default => $this->markQueuedSyncFailed($state, sprintf('Unknown Nova Poshta sync stage [%s].', $stage)),
        };
    }

    /**
     * @throws Throwable
     * @return array<string, int>
     */
    public function syncRegions(): array
    {
        $response = $this->api_service->getRegions();
        $rows = $this->extractRows($response, self::STAGE_REGIONS);
        $normalized_rows = $this->normalizeRegionRows($rows);

        if ($normalized_rows === []) {
            throw new RuntimeException('Nova Poshta API returned no usable regions.');
        }

        return [
            'imported' => $this->persistRows(
                $normalized_rows,
                static function (): void {
                    NovaPoshtaRegion::query()->delete();
                },
                static function (array $rows): void {
                    NovaPoshtaRegion::query()->insert($rows);
                },
                true,
            ),
        ];
    }

    /**
     * @throws Throwable
     * @return array<string, int>
     */
    public function syncCities(): array
    {
        $region_ids_by_ref = NovaPoshtaRegion::query()->pluck('id', 'ref')->all();
        $response = $this->api_service->getCities(1);
        $rows = $this->extractRows($response, self::STAGE_CITIES);
        $total_pages = $this->resolveTotalPages($response);
        $imported_rows = 0;

        $normalized_rows = $this->normalizeCityRows($rows, $region_ids_by_ref);

        if ($total_pages === 1 && $normalized_rows === []) {
            throw new RuntimeException('Nova Poshta API returned no usable cities.');
        }

        $imported_rows += $this->persistRows(
            $normalized_rows,
            static function (): void {
                NovaPoshtaCity::query()->delete();
            },
            static function (array $rows): void {
                NovaPoshtaCity::query()->insert($rows);
            },
            true,
        );

        for ($page = 2; $page <= $total_pages; $page++) {
            $page_response = $this->api_service->getCities($page);
            $page_rows = $this->extractRows($page_response, self::STAGE_CITIES);
            $page_normalized_rows = $this->normalizeCityRows($page_rows, $region_ids_by_ref);

            $imported_rows += $this->persistRows(
                $page_normalized_rows,
                static function (): void {
                    NovaPoshtaCity::query()->delete();
                },
                static function (array $rows): void {
                    NovaPoshtaCity::query()->insert($rows);
                },
                false,
            );
        }

        return [
            'imported' => $imported_rows,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function syncPostOffices(): array
    {
        return $this->syncWarehousesTable(NovaPoshtaPostOffice::class, false);
    }

    /**
     * @return array<string, int>
     */
    public function syncPoshtomats(): array
    {
        return $this->syncWarehousesTable(NovaPoshtaPoshtomat::class, true);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private function extractRows(array $response, string $dataset_name): array
    {
        if (Arr::get($response, 'success', false) !== true) {
            throw new RuntimeException(sprintf('Nova Poshta API returned unsuccessful response for %s.', $dataset_name));
        }

        $rows = Arr::get($response, 'data', []);

        if (! is_array($rows) || $rows === []) {
            throw new RuntimeException(sprintf('Nova Poshta API returned empty payload for %s.', $dataset_name));
        }

        return array_values(array_filter($rows, static fn (mixed $row): bool => is_array($row)));
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function resolveTotalPages(array $response): int
    {
        $total_count = (int) Arr::get($response, 'info.totalCount', 0);

        if ($total_count < 1) {
            throw new RuntimeException('Nova Poshta API did not return a total count for paginated data.');
        }

        $limit = $this->getLimit();
        $total_pages = (int) ceil($total_count / $limit);

        return max(1, $total_pages);
    }

    /**
     * @return array<string, mixed>
     */
    private function makeInitialQueuedSyncState(): array
    {
        return [
            'is_running' => false,
            'stage' => null,
            'phase' => null,
            'current_page' => 1,
            'total_pages' => 1,
            'stage_total_rows' => 0,
            'stage_processed_rows' => 0,
            'stage_progress' => 0,
            'overall_progress' => 0,
            'message' => null,
            'summary' => [],
            'started_at' => null,
            'updated_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function processRegionsStage(array $state): array
    {
        try {
            $summary = $this->syncRegions();

            $state['summary'][self::STAGE_REGIONS] = $summary;
            $state['stage'] = self::STAGE_CITIES;
            $state['phase'] = self::PHASE_COLLECT;
            $state['current_page'] = 1;
            $state['total_pages'] = 1;
            $state['stage_total_rows'] = 0;
            $state['stage_processed_rows'] = 0;
            $state['stage_progress'] = 0;
            $state['overall_progress'] = 25;
            $state['message'] = __('admin/modules/nova_poshta.sync.messages.regions_completed');

            return $this->persistQueuedSyncState($state);
        } catch (Throwable $throwable) {
            return $this->markQueuedSyncFailed($state, $throwable->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function processCitiesStage(array $state): array
    {
        return $this->processPaginatedStage(
            $state,
            self::STAGE_CITIES,
            fn (int $page): array => $this->api_service->getCities($page),
            function (array $rows): array {
                $region_ids_by_ref = NovaPoshtaRegion::query()->pluck('id', 'ref')->all();

                return $this->normalizeCityRows($rows, $region_ids_by_ref);
            },
            static function (array $normalized_rows, bool $reset_table): int {
                return self::persistQueueRows(
                    NovaPoshtaCity::class,
                    $normalized_rows,
                    $reset_table,
                );
            },
            self::STAGE_POST_OFFICES,
            __('admin/modules/nova_poshta.sync.messages.cities_completed'),
        );
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function processWarehousesStage(array $state, string $model_class, bool $is_poshtomat): array
    {
        return $this->processPaginatedStage(
            $state,
            $is_poshtomat ? self::STAGE_POSHTOMATS : self::STAGE_POST_OFFICES,
            fn (int $page): array => $this->api_service->getWarehouses($page),
            function (array $rows) use ($is_poshtomat): array {
                $city_ids_by_ref = NovaPoshtaCity::query()->pluck('id', 'ref')->all();

                return $this->normalizeWarehouseRows($rows, $city_ids_by_ref, $is_poshtomat);
            },
            static function (array $normalized_rows, bool $reset_table) use ($model_class): int {
                return self::persistQueueRows(
                    $model_class,
                    $normalized_rows,
                    $reset_table,
                );
            },
            $is_poshtomat ? null : self::STAGE_POSHTOMATS,
            $is_poshtomat
                ? __('admin/modules/nova_poshta.sync.messages.poshtomats_completed')
                : __('admin/modules/nova_poshta.sync.messages.post_offices_completed'),
        );
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  Closure(int): array<string, mixed>  $response_resolver
     * @param  Closure(array<int, array<string, mixed>>): array<int, array<string, mixed>>  $normalize_rows_callback
     * @param  Closure(array<int, array<string, mixed>>, bool): int  $persist_rows_callback
     * @return array<string, mixed>
     */
    private function processPaginatedStage(
        array $state,
        string $stage,
        Closure $response_resolver,
        Closure $normalize_rows_callback,
        Closure $persist_rows_callback,
        ?string $next_stage,
        string $completed_message,
    ): array {
        $current_page = max(1, (int) Arr::get($state, 'current_page', 1));
        $total_pages = max(1, (int) Arr::get($state, 'total_pages', 1));
        $stage_total_rows = max(1, (int) Arr::get($state, 'stage_total_rows', 0));
        $stage_processed_rows = max(0, (int) Arr::get($state, 'stage_processed_rows', 0));

        try {
            $response = $response_resolver($current_page);
            $rows = $this->extractRows($response, $stage);

            if ($current_page === 1) {
                $total_pages = $this->resolveTotalPages($response);
                $stage_total_rows = max(1, (int) Arr::get($response, 'info.totalCount', 0));
                $state['total_pages'] = $total_pages;
                $state['stage_total_rows'] = $stage_total_rows;
                $state['stage_processed_rows'] = 0;
            }

            $normalized_rows = $normalize_rows_callback($rows);

            if ($current_page === 1 && $total_pages === 1 && $normalized_rows === []) {
                throw new RuntimeException(sprintf('Nova Poshta API returned no usable %s.', $stage));
            }

            $imported_rows = (int) $persist_rows_callback($normalized_rows, $current_page === 1);
            $state['summary'][$stage] = [
                'imported' => (int) Arr::get($state, 'summary.' . $stage . '.imported', 0) + $imported_rows,
            ];

            $stage_processed_rows = min($stage_total_rows, $stage_processed_rows + count($rows));
            $state['stage_processed_rows'] = $stage_processed_rows;
            $state['stage_progress'] = (int) floor(($stage_processed_rows / $stage_total_rows) * 100);

            if ($current_page >= $total_pages) {
                $state['stage_progress'] = 100;
                $state['overall_progress'] = $this->getOverallProgressForCompletedStage($stage);
                $state['message'] = $completed_message;

                if ($next_stage === null) {
                    $state['is_running'] = false;
                    $state['stage'] = 'completed';
                    $state['phase'] = 'completed';
                    $state['completed_at'] = now()->toIso8601String();
                    $state['overall_progress'] = 100;
                    $state['message'] = __('admin/modules/nova_poshta.sync.messages.completed');

                    Cache::put(self::LAST_SYNC_SUMMARY_CACHE_KEY, $state['summary'], now()->addDay());
                } else {
                    $state['stage'] = $next_stage;
                    $state['phase'] = self::PHASE_COLLECT;
                    $state['current_page'] = 1;
                    $state['total_pages'] = 1;
                    $state['stage_total_rows'] = 0;
                    $state['stage_processed_rows'] = 0;
                    $state['stage_progress'] = 0;
                    $state['overall_progress'] = $this->getOverallProgressForCompletedStage($stage);
                    $state['message'] = __('admin/modules/nova_poshta.sync.messages.next_stage', [
                        'stage' => $this->getStageLabel($next_stage),
                    ]);
                }
            } else {
                $state['current_page'] = $current_page + 1;
                $state['overall_progress'] = $this->getOverallProgressForStage($stage, $state['stage_progress']);
                $state['message'] = __('admin/modules/nova_poshta.sync.messages.collecting_page', [
                    'current' => $current_page,
                    'total' => $total_pages,
                    'stage' => $this->getStageLabel($stage),
                ]);
            }

            return $this->persistQueuedSyncState($state);
        } catch (Throwable $throwable) {
            return $this->markQueuedSyncFailed($state, $throwable->getMessage());
        }
    }

    /**
     * @param  class-string<NovaPoshtaRegion|NovaPoshtaCity|NovaPoshtaPostOffice|NovaPoshtaPoshtomat>  $model_class
     */
    private static function persistQueueRows(string $model_class, array $normalized_rows, bool $reset_table): int
    {
        if ($normalized_rows === []) {
            return 0;
        }

        return DB::transaction(function () use ($model_class, $normalized_rows, $reset_table): int {
            if ($reset_table) {
                $model_class::query()->delete();
            }

            $model_class::query()->insert($normalized_rows);

            return count($normalized_rows);
        });
    }

    /**
     * @param  Closure(): void  $reset_table
     * @param  Closure(array<int, array<string, mixed>>): void  $insert_rows
     */
    private function persistRows(
        array $normalized_rows,
        Closure $reset_table,
        Closure $insert_rows,
        bool $reset_before_insert,
    ): int {
        if ($normalized_rows === []) {
            return 0;
        }

        return DB::transaction(function () use (
            $normalized_rows,
            $reset_table,
            $insert_rows,
            $reset_before_insert,
        ): int {
            if ($reset_before_insert) {
                $reset_table();
            }

            $insert_rows($normalized_rows);

            return count($normalized_rows);
        });
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  string  $message
     * @return array<string, mixed>
     */
    private function markQueuedSyncFailed(array $state, string $message): array
    {
        $state['is_running'] = false;
        $state['stage'] = 'failed';
        $state['phase'] = 'failed';
        $state['message'] = $message;

        return $this->persistQueuedSyncState($state);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function persistQueuedSyncState(array $state): array
    {
        $state['updated_at'] = now()->toIso8601String();

        Cache::put(self::SYNC_STATE_CACHE_KEY, $state, now()->addDay());

        return $state;
    }

    private function getOverallProgressForStage(string $stage, int $stage_progress): int
    {
        $offset = match ($stage) {
            self::STAGE_REGIONS => 0,
            self::STAGE_CITIES => 25,
            self::STAGE_POST_OFFICES => 50,
            self::STAGE_POSHTOMATS => 75,
            default => 0,
        };

        return min(100, $offset + (int) floor(($stage_progress / 100) * 25));
    }

    private function getOverallProgressForCompletedStage(string $stage): int
    {
        return match ($stage) {
            self::STAGE_REGIONS => 25,
            self::STAGE_CITIES => 50,
            self::STAGE_POST_OFFICES => 75,
            self::STAGE_POSHTOMATS => 100,
            default => 0,
        };
    }

    private function getStageLabel(string $stage): string
    {
        return match ($stage) {
            self::STAGE_REGIONS => (string) __('admin/modules/nova_poshta.sync.stages.regions'),
            self::STAGE_CITIES => (string) __('admin/modules/nova_poshta.sync.stages.cities'),
            self::STAGE_POST_OFFICES => (string) __('admin/modules/nova_poshta.sync.stages.post_offices'),
            self::STAGE_POSHTOMATS => (string) __('admin/modules/nova_poshta.sync.stages.poshtomats'),
            default => $stage,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRegionRows(array $rows): array
    {
        return array_values(array_map(
            fn (array $row): array => [
                'ref' => (string) Arr::get($row, 'Ref', ''),
                'regions_center' => (string) Arr::get($row, 'AreasCenter', ''),
                'description' => (string) Arr::get($row, 'Description', ''),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            array_filter($rows, static fn (array $row): bool => filled((string) Arr::get($row, 'Ref', ''))),
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, int>  $region_ids_by_ref
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCityRows(array $rows, array $region_ids_by_ref): array
    {
        $normalized_rows = [];

        foreach ($rows as $row) {
            $city_ref = (string) Arr::get($row, 'Ref', '');
            $region_ref = (string) Arr::get($row, 'Area', '');
            $region_id = $region_ids_by_ref[$region_ref] ?? null;

            if (blank($city_ref) || $region_id === null) {
                continue;
            }

            $city_name = (string) Arr::get($row, 'Description', '');

            $normalized_rows[] = [
                'nova_poshta_region_id' => $region_id,
                'ref' => $city_ref,
                'region' => $region_ref,
                'description' => $this->formatCityDescription($city_name, (string) Arr::get($row, 'AreaDescription', '')),
                'city_name' => $city_name,
                'latitude' => (string) Arr::get($row, 'Latitude', ''),
                'longitude' => (string) Arr::get($row, 'Longitude', ''),
                'city_id' => $this->tryInteger(Arr::get($row, 'CityID')),
                'region_description' => (string) Arr::get($row, 'AreaDescription', ''),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $normalized_rows;
    }

    /**
     * @param  class-string<NovaPoshtaPostOffice|NovaPoshtaPoshtomat>  $model_class
     * @return array<string, int>
     */
    private function syncWarehousesTable(string $model_class, bool $is_poshtomat): array
    {
        $city_ids_by_ref = NovaPoshtaCity::query()->pluck('id', 'ref')->all();
        $response = $this->api_service->getWarehouses(1);
        $rows = $this->extractRows($response, 'warehouses');
        $total_pages = $this->resolveTotalPages($response);
        $imported_rows = 0;
        $normalized_rows = $this->normalizeWarehouseRows($rows, $city_ids_by_ref, $is_poshtomat);
        $empty_message = $is_poshtomat
            ? 'Nova Poshta API returned no usable poshtomats.'
            : 'Nova Poshta API returned no usable post offices.';

        if ($total_pages === 1 && $normalized_rows === []) {
            throw new RuntimeException($empty_message);
        }

        $imported_rows += $this->persistRows(
            $normalized_rows,
            static function () use ($model_class): void {
                $model_class::query()->delete();
            },
            static function (array $rows) use ($model_class): void {
                $model_class::query()->insert($rows);
            },
            true,
        );

        for ($page = 2; $page <= $total_pages; $page++) {
            $page_response = $this->api_service->getWarehouses($page);
            $page_rows = $this->extractRows($page_response, 'warehouses');
            $page_normalized_rows = $this->normalizeWarehouseRows($page_rows, $city_ids_by_ref, $is_poshtomat);

            $imported_rows += $this->persistRows(
                $page_normalized_rows,
                static function () use ($model_class): void {
                    $model_class::query()->delete();
                },
                static function (array $rows) use ($model_class): void {
                    $model_class::query()->insert($rows);
                },
                false,
            );
        }

        return [
            'imported' => $imported_rows,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, int>  $city_ids_by_ref
     * @return array<int, array<string, mixed>>
     */
    private function normalizeWarehouseRows(array $rows, array $city_ids_by_ref, bool $is_poshtomat): array
    {
        $normalized_rows = [];

        foreach ($rows as $row) {
            $description = (string) Arr::get($row, 'Description', '');
            $row_is_poshtomat = Str::startsWith(Str::lower($description), 'поштомат ');

            if ($row_is_poshtomat !== $is_poshtomat) {
                continue;
            }

            $city_ref = (string) Arr::get($row, 'SettlementRef', '');
            $city_id = $city_ids_by_ref[$city_ref] ?? null;

            if (blank($city_ref) || $city_id === null) {
                continue;
            }

            $normalized_rows[] = [
                'nova_poshta_city_id' => $city_id,
                'ref' => (string) Arr::get($row, 'Ref', ''),
                'city_ref' => $city_ref,
                'description' => $description,
                'latitude' => (string) Arr::get($row, 'Latitude', ''),
                'longitude' => (string) Arr::get($row, 'Longitude', ''),
                'schedule' => $this->normalizeSchedule(Arr::get($row, 'Schedule')),
                'number' => $this->extractWarehouseNumber($description),
                'city_description' => (string) Arr::get($row, 'CityDescription', ''),
                'site_key' => $this->tryInteger(Arr::get($row, 'SiteKey')),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $normalized_rows;
    }

    private function getLimit(): int
    {
        return max(1, (int) $this->config->get('api.limit', 500));
    }

    /**
     * @param  mixed  $schedule
     */
    private function normalizeSchedule(mixed $schedule): ?string
    {
        if (! is_array($schedule) || $schedule === []) {
            return null;
        }

        $encoded_schedule = json_encode($schedule, JSON_UNESCAPED_UNICODE);

        return is_string($encoded_schedule) ? $encoded_schedule : null;
    }

    private function tryInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function extractWarehouseNumber(string $description): ?int
    {
        $pattern = '/(?<=відділення №)\d+|(?<=відділення № )\d+|(?<=поштомат ["\']нова пошта["\'] №)\d+|(?<=пункт №)\d+|(?<=поштомат ["\']нова пошта["\']№)\d+/ui';

        preg_match($pattern, $description, $matches);

        if (isset($matches[0])) {
            return (int) $matches[0];
        }

        preg_match('/№\s*(\d+)/u', $description, $matches);

        if (isset($matches[1])) {
            return (int) $matches[1];
        }

        return null;
    }

    private function formatCityDescription(string $city_name, string $area_description): string
    {
        $region_name = trim($area_description . ' обл.');

        if (Str::contains($city_name, $region_name)) {
            return $city_name;
        }

        return $city_name . ' (' . $region_name . ')';
    }
}
