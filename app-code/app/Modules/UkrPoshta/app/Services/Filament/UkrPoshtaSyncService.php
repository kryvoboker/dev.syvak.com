<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Services\Filament;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\UkrPoshta\Models\UkrPoshtaCity;
use Modules\UkrPoshta\Models\UkrPoshtaDistrict;
use Modules\UkrPoshta\Models\UkrPoshtaPostOffice;
use Modules\UkrPoshta\Models\UkrPoshtaRegion;
use Modules\UkrPoshta\Services\UkrPoshtaApiService;
use RuntimeException;
use Throwable;

readonly class UkrPoshtaSyncService
{
    private const string SYNC_STATE_CACHE_KEY = 'ukr_poshta.sync_state';

    private const string LAST_SYNC_SUMMARY_CACHE_KEY = 'ukr_poshta.last_sync_summary';

    private const string STAGE_REGIONS = 'regions';

    private const string STAGE_DISTRICTS = 'districts';

    private const string STAGE_CITIES = 'cities';

    private const string STAGE_POST_OFFICES = 'post_offices';

    private const string PHASE_COLLECT = 'collect';

    private const string PHASE_FINALIZE = 'finalize';

    private const string PHASE_STOPPED = 'stopped';

    private const string PHASE_FAILED = 'failed';

    public function __construct(
        private UkrPoshtaApiService $api_service,
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
                'districts' => $this->syncDistricts(),
                'cities' => $this->syncCities(),
                'post_offices' => $this->syncPostOffices(),
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Ukr Poshta sync failed.', [
                'exception' => $throwable,
            ]);

            throw new RuntimeException('Ukr Poshta sync failed.', previous: $throwable);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function startQueuedSync(): array
    {
        $current_state = $this->getQueuedSyncState();

        if (Arr::get($current_state, 'is_running', false) === true) {
            throw new RuntimeException('Ukr Poshta sync is already running.');
        }

        $state = $this->makeInitialQueuedSyncState();
        $state['is_running'] = true;
        $state['stage'] = self::STAGE_REGIONS;
        $state['phase'] = self::PHASE_COLLECT;
        $state['started_at'] = now()->toIso8601String();
        $state['updated_at'] = now()->toIso8601String();
        $state['message'] = __('admin/modules/ukr_poshta.sync.messages.started');
        $state['summary'] = $this->makeInitialQueuedSyncSummary();

        Cache::put(self::SYNC_STATE_CACHE_KEY, $state, now()->addDay());

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function requestQueuedSyncStop(): array
    {
        $state = $this->getQueuedSyncState();
        $state['stop_requested'] = true;
        $state['message'] = __('admin/modules/ukr_poshta.sync.messages.stop_requested');
        $state['updated_at'] = now()->toIso8601String();

        $this->saveQueuedSyncState($state);

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
     * @throws Throwable
     * @return array<string, mixed>
     */
    public function processQueuedSyncStep(): array
    {
        $state = $this->getQueuedSyncState();

        if (Arr::get($state, 'is_running', false) !== true) {
            return $state;
        }

        if (Arr::get($state, 'stop_requested', false) === true) {
            return $this->markQueuedSyncStopped($state);
        }

        $stage = (string)Arr::get($state, 'stage', '');

        if ($stage === self::STAGE_DISTRICTS) {
            return $this->processQueueStage(
                $state,
                self::STAGE_DISTRICTS,
                fn (int $region_id): int => $this->syncDistrictBatch($region_id),
                function (): void {
                    UkrPoshtaDistrict::query()->delete();
                },
                self::STAGE_CITIES,
                'districts',
                self::STAGE_DISTRICTS,
                __('admin/modules/ukr_poshta.sync.messages.districts_completed'),
                fn (): array => $this->getRegionIds(),
                fn (): array => $this->getDistrictIds(),
            );
        }

        try {
            return match ($stage) {
                self::STAGE_REGIONS => $this->processRegionsStage($state),
                self::STAGE_CITIES => $this->processQueueStage(
                    $state,
                    self::STAGE_CITIES,
                    fn (int $district_id): int => $this->syncCityBatch($district_id),
                    function (): void {
                        UkrPoshtaCity::query()->delete();
                    },
                    self::STAGE_POST_OFFICES,
                    'cities',
                    self::STAGE_CITIES,
                    __('admin/modules/ukr_poshta.sync.messages.cities_completed'),
                    fn (): array => $this->getDistrictIds(),
                    fn (): array => $this->getDistrictIds(),
                ),
                self::STAGE_POST_OFFICES => $this->processQueueStage(
                    $state,
                    self::STAGE_POST_OFFICES,
                    fn (int $district_id): int => $this->syncPostOfficeBatch($district_id),
                    function (): void {
                        UkrPoshtaPostOffice::query()->delete();
                    },
                    'completed',
                    'post_offices',
                    self::STAGE_POST_OFFICES,
                    __('admin/modules/ukr_poshta.sync.messages.completed'),
                    fn (): array => $this->getDistrictIds(),
                    static fn (): array => [],
                ),
                'completed', 'failed', 'stopped' => $state,
                default => $this->markQueuedSyncFailed($state, sprintf('Unknown Ukr Poshta sync stage [%s].', Arr::get($state, 'stage', ''))),
            };
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Ukr Poshta queued sync step failed.', [
                'stage' => (string)Arr::get($state, 'stage', ''),
                'phase' => (string)Arr::get($state, 'phase', ''),
                'error' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            $state['updated_at'] = now()->toIso8601String();
            $state['message'] = sprintf(
                'Ukr Poshta sync hit a recoverable error during stage [%s]: %s',
                Arr::get($state, 'stage', ''),
                $throwable->getMessage(),
            );

            $this->saveQueuedSyncState($state);

            return $state;
        }
    }

    /**
     * @throws Throwable
     * @return array<string, int>
     */
    public function syncRegions(): array
    {
        try {
            $response = $this->api_service->getRegions();
            $rows = $this->normalizeRegionRows($this->extractRows($response));

            if ($rows === []) {
                throw new RuntimeException('Ukr Poshta API returned no usable regions.');
            }

            UkrPoshtaRegion::query()->delete();
            UkrPoshtaRegion::query()->insert($rows);
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Ukr Poshta regions sync failed, keeping existing data.', [
                'exception' => $throwable,
            ]);

            return ['imported' => 0];
        }

        $summary = $this->getLastSyncSummary();
        $summary['regions'] = ['imported' => count($rows)];
        Cache::put(self::LAST_SYNC_SUMMARY_CACHE_KEY, $summary, now()->addDay());

        return ['imported' => count($rows)];
    }

    /**
     * @throws Throwable
     * @return array<string, int>
     */
    public function syncDistricts(): array
    {
        $region_ids = $this->getRegionIds();

        if ($region_ids === []) {
            Log::channel('stack')->warning('Ukr Poshta district sync skipped because no regions are available.');

            return ['imported' => 0];
        }

        UkrPoshtaDistrict::query()->delete();

        $imported_rows = 0;

        foreach ($region_ids as $region_id) {
            $imported_rows += $this->syncDistrictBatch($region_id);
        }

        $summary = $this->getLastSyncSummary();
        $summary['districts'] = ['imported' => $imported_rows];
        Cache::put(self::LAST_SYNC_SUMMARY_CACHE_KEY, $summary, now()->addDay());

        return ['imported' => $imported_rows];
    }

    /**
     * @throws Throwable
     * @return array<string, int>
     */
    public function syncCities(): array
    {
        $district_ids = $this->getDistrictIds();

        if ($district_ids === []) {
            Log::channel('stack')->warning('Ukr Poshta city sync skipped because no districts are available.');

            return ['imported' => 0];
        }

        UkrPoshtaCity::query()->delete();

        $imported_rows = 0;

        foreach ($district_ids as $district_id) {
            $imported_rows += $this->syncCityBatch($district_id);
        }

        $summary = $this->getLastSyncSummary();
        $summary['cities'] = ['imported' => $imported_rows];
        Cache::put(self::LAST_SYNC_SUMMARY_CACHE_KEY, $summary, now()->addDay());

        return ['imported' => $imported_rows];
    }

    /**
     * @throws Throwable
     * @return array<string, int>
     */
    public function syncPostOffices(): array
    {
        $district_ids = $this->getDistrictIds();

        if ($district_ids === []) {
            Log::channel('stack')->warning('Ukr Poshta post office sync skipped because no districts are available.');

            return ['imported' => 0];
        }

        UkrPoshtaPostOffice::query()->delete();

        $imported_rows = 0;

        foreach ($district_ids as $district_id) {
            $imported_rows += $this->syncPostOfficeBatch($district_id);
        }

        $summary = $this->getLastSyncSummary();
        $summary['post_offices'] = ['imported' => $imported_rows];
        Cache::put(self::LAST_SYNC_SUMMARY_CACHE_KEY, $summary, now()->addDay());

        return ['imported' => $imported_rows];
    }

    /**
     * @param array<string, mixed> $response
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractRows(array $response): array
    {
        $rows = Arr::get($response, 'data', []);

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @param array<string, mixed>        $state
     * @param string                      $stage
     * @param callable(int): int          $batch_callback
     * @param callable(): void            $reset_callback
     * @param string                      $next_stage
     * @param string                      $summary_key
     * @param string                      $stage_label
     * @param string                      $completion_message
     * @param callable(): array<int, int> $queue_provider
     * @param callable|null               $next_queue_provider
     *
     * @return array<string, mixed>
     */
    private function processQueueStage(
        array $state,
        string $stage,
        callable $batch_callback,
        callable $reset_callback,
        string $next_stage,
        string $summary_key,
        string $stage_label,
        string $completion_message,
        callable $queue_provider,
        ?callable $next_queue_provider = null,
    ): array {
        if (Arr::get($state, 'stage_initialized', false) !== true) {
            $reset_callback();
            $state['stage_initialized'] = true;
            $state['stage_queue'] = array_values(array_map('intval', (array) $queue_provider()));
            $state['stage_total_rows'] = count($state['stage_queue']);
            $state['stage_processed_rows'] = 0;
            $state['summary'] = $this->normalizeQueuedSyncSummary((array) Arr::get($state, 'summary', []));
            $state['summary'][$summary_key] = [
                'processed' => 0,
                'total' => $state['stage_total_rows'],
                'imported' => 0,
            ];

            if ($state['stage_queue'] === []) {
                return $this->finalizeEmptyQueuedSyncStage(
                    $state,
                    $summary_key,
                    $next_stage,
                    $completion_message,
                    $queue_provider,
                    $next_queue_provider,
                );
            }
        }

        $queue = array_values(array_map('intval', (array)Arr::get($state, 'stage_queue', [])));

        if ($queue === []) {
            return $this->finalizeEmptyQueuedSyncStage(
                $state,
                $summary_key,
                $next_stage,
                $completion_message,
                $queue_provider,
                $next_queue_provider,
            );
        }

        $current_id = array_shift($queue);
        try {
            $imported_rows = $batch_callback($current_id);
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Ukr Poshta queued sync batch failed, continuing.', [
                'stage' => $stage,
                'item_id' => $current_id,
                'error' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            $imported_rows = 0;
        }
        $current_processed_rows = (int)Arr::get($state, 'summary.' . $summary_key . '.processed', 0);
        $current_total_rows = max(0, (int)Arr::get($state, 'summary.' . $summary_key . '.total', Arr::get($state, 'stage_total_rows', 0)));
        $next_processed_rows = $current_processed_rows + $imported_rows;

        $summary = $this->getLastSyncSummary();
        $summary[$summary_key] = [
            'imported' => (int)Arr::get($summary, $summary_key . '.imported', 0) + $imported_rows,
        ];
        Cache::put(self::LAST_SYNC_SUMMARY_CACHE_KEY, $summary, now()->addDay());

        $state['summary'] = $this->normalizeQueuedSyncSummary((array)Arr::get($state, 'summary', []));
        $state['summary'][$summary_key] = [
            'processed' => $next_processed_rows,
            'total' => $next_processed_rows,
            'imported' => (int)Arr::get($state, 'summary.' . $summary_key . '.imported', 0) + $imported_rows,
        ];
        $state['stage_queue'] = $queue;
        $state['stage_processed_rows'] = $next_processed_rows;
        $state['overall_progress'] = $this->resolveOverallProgress($stage, $next_processed_rows, $current_total_rows);
        $state['message'] = __('admin/modules/ukr_poshta.sync.messages.collecting_item', [
            'stage' => $stage_label,
            'current' => $next_processed_rows,
            'total' => $current_total_rows,
        ]);
        $state['updated_at'] = now()->toIso8601String();

        if ($queue !== []) {
            $this->saveQueuedSyncState($state);

            return $state;
        }

        $state['stage_initialized'] = false;
        $state['stage_queue'] = [];
        $state['stage_total_rows'] = 0;
        $state['stage_processed_rows'] = 0;
        $state['message'] = $completion_message;
        $state['stage'] = $next_stage;
        $state['summary'] = $this->normalizeQueuedSyncSummary((array)Arr::get($state, 'summary', []));
        $state['summary'][$summary_key] = [
            'processed' => (int)Arr::get($state, 'summary.' . $summary_key . '.total', 0),
            'total' => (int)Arr::get($state, 'summary.' . $summary_key . '.total', 0),
            'imported' => (int)Arr::get($state, 'summary.' . $summary_key . '.imported', 0),
        ];

        if ($next_stage === 'completed') {
            return $this->markQueuedSyncCompleted($state);
        }

        $state['stage_queue'] = array_values(array_map('intval', ($next_queue_provider ?? $queue_provider)()));
        $state['stage_total_rows'] = count($state['stage_queue']);
        $state['stage_processed_rows'] = 0;
        $state['stage_initialized'] = false;
        $state['updated_at'] = now()->toIso8601String();
        $state['summary'][$next_stage] = [
            'processed' => 0,
            'total' => $state['stage_total_rows'],
            'imported' => 0,
        ];
        $this->saveQueuedSyncState($state);

        return $state;
    }

    /**
     * @param array<string, mixed>        $state
     * @param string                      $summary_key
     * @param string                      $next_stage
     * @param string                      $completion_message
     * @param callable(): array<int, int> $queue_provider
     * @param callable|null               $next_queue_provider
     *
     * @return array<string, mixed>
     */
    private function finalizeEmptyQueuedSyncStage(
        array $state,
        string $summary_key,
        string $next_stage,
        string $completion_message,
        callable $queue_provider,
        ?callable $next_queue_provider = null,
    ): array {
        $state['stage_initialized'] = false;
        $state['stage_queue'] = [];
        $state['stage_total_rows'] = 0;
        $state['stage_processed_rows'] = 0;
        $state['message'] = $completion_message;
        $state['stage'] = $next_stage;
        $state['summary'] = $this->normalizeQueuedSyncSummary((array) Arr::get($state, 'summary', []));
        $state['summary'][$summary_key] = [
            'processed' => (int) Arr::get($state, 'summary.' . $summary_key . '.total', 0),
            'total' => (int) Arr::get($state, 'summary.' . $summary_key . '.total', 0),
            'imported' => (int) Arr::get($state, 'summary.' . $summary_key . '.imported', 0),
        ];

        if ($next_stage === 'completed') {
            return $this->markQueuedSyncCompleted($state);
        }

        $state['stage_queue'] = array_values(array_map('intval', ($next_queue_provider ?? $queue_provider)()));
        $state['stage_total_rows'] = count($state['stage_queue']);
        $state['stage_processed_rows'] = 0;
        $state['stage_initialized'] = false;
        $state['updated_at'] = now()->toIso8601String();
        $state['summary'][$next_stage] = [
            'processed' => 0,
            'total' => $state['stage_total_rows'],
            'imported' => 0,
        ];
        $this->saveQueuedSyncState($state);

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     *
     * @throws Throwable
     * @return array<string, mixed>
     */
    private function processRegionsStage(array $state): array
    {
        if (Arr::get($state, 'stage_initialized', false) !== true) {
            $state['stage_initialized'] = true;
            $state['stage_total_rows'] = 1;
            $state['stage_processed_rows'] = 0;
            $state['summary'] = $this->normalizeQueuedSyncSummary((array)Arr::get($state, 'summary', []));
            $state['summary'][self::STAGE_REGIONS] = [
                'processed' => 0,
                'total' => 0,
                'imported' => 0,
            ];
        }

        $imported_rows = 0;

        try {
            $response = $this->api_service->getRegions();
            $rows = $this->normalizeRegionRows($this->extractRows($response));

            if ($rows === []) {
                throw new RuntimeException('Ukr Poshta API returned no usable regions.');
            }

            UkrPoshtaRegion::query()->delete();
            UkrPoshtaRegion::query()->insert($rows);
            $region_ids = array_values(array_map(static fn (array $row): int => (int)$row['region_id'], $rows));
            $imported_rows = count($rows);

            $summary = $this->getLastSyncSummary();
            $summary['regions'] = ['imported' => $imported_rows];
            Cache::put(self::LAST_SYNC_SUMMARY_CACHE_KEY, $summary, now()->addDay());
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Ukr Poshta regions sync failed, continuing with existing regions.', [
                'error' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            $region_ids = $this->getRegionIds();
        }

        $state['summary'] = $this->normalizeQueuedSyncSummary((array)Arr::get($state, 'summary', []));
        $state['summary'][self::STAGE_REGIONS] = [
            'processed' => $imported_rows,
            'total' => max($imported_rows, count($region_ids)),
            'imported' => $imported_rows,
        ];
        $state['region_ids'] = $region_ids;
        $state['stage'] = self::STAGE_DISTRICTS;
        $state['stage_queue'] = $region_ids;
        $state['stage_total_rows'] = count($region_ids);
        $state['stage_processed_rows'] = 0;
        $state['stage_initialized'] = false;
        $state['overall_progress'] = 25;
        $state['message'] = __('admin/modules/ukr_poshta.sync.messages.regions_completed');
        $state['updated_at'] = now()->toIso8601String();

        $this->saveQueuedSyncState($state);

        return $state;
    }

    /**
     * @return array<int, int>
     */
    private function getRegionIds(): array
    {
        return UkrPoshtaRegion::query()
            ->whereNotNull('region_id')
            ->orderBy('region_id')
            ->pluck('region_id')
            ->map(static fn (mixed $value): int => (int)$value)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function getDistrictIds(): array
    {
        return UkrPoshtaDistrict::query()
            ->whereNotNull('district_id')
            ->orderBy('district_id')
            ->pluck('district_id')
            ->map(static fn (mixed $value): int => (int)$value)
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRegionRows(array $rows): array
    {
        return array_values(array_filter(array_map(function (array $row): array {
            $region_id = (int)$this->rowValue($row, ['REGION_ID', 'region_id']);
            $region_ua = trim((string)$this->rowValue($row, ['REGION_UA', 'region_ua', 'NAME', 'name']));

            if ($region_id === 0 || $region_ua === '') {
                return [];
            }

            return [
                'region_id' => $region_id,
                'region_ua' => $region_ua,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $rows)));
    }

    /**
     * @param int $region_id
     *
     * @throws Throwable
     * @return int
     */
    private function syncDistrictBatch(int $region_id): int
    {
        try {
            $response = $this->api_service->getDistricts($region_id);
            $rows = $this->normalizeDistrictRows($this->extractRows($response), $region_id);

            if ($rows !== []) {
                UkrPoshtaDistrict::query()->insert($rows);
            }

            return count($rows);
        } catch (Throwable $e) {
            Log::channel('stack')->error('Ukr Poshta district batch failed, skipping region.', [
                'region_id' => $region_id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return 0;
        }
    }

    /**
     * @param int $district_id
     *
     * @throws Throwable
     * @return int
     */
    private function syncCityBatch(int $district_id): int
    {
        try {
            $response = $this->api_service->getCities($district_id);
            $rows = $this->normalizeCityRows($this->extractRows($response), $district_id);

            if ($rows !== []) {
                UkrPoshtaCity::query()->insert($rows);
            }

            return count($rows);
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Ukr Poshta city batch failed, skipping district.', [
                'district_id' => $district_id,
                'error' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            return 0;
        }
    }

    /**
     * @param int $district_id
     *
     * @throws Throwable
     * @return int
     */
    private function syncPostOfficeBatch(int $district_id): int
    {
        try {
            $response = $this->api_service->getPostOffices($district_id);
            $rows = $this->normalizePostOfficeRows($this->extractRows($response), $district_id);

            if ($rows !== []) {
                UkrPoshtaPostOffice::query()->insert($rows);
            }

            return count($rows);
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Ukr Poshta post office batch failed, skipping district.', [
                'district_id' => $district_id,
                'error' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);

            return 0;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param int                              $region_id
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeDistrictRows(array $rows, int $region_id): array
    {
        return array_values(array_filter(array_map(function (array $row) use ($region_id): array {
            $district_id = (int)$this->rowValue($row, ['DISTRICT_ID', 'district_id']);
            $region_ua = trim((string)$this->rowValue($row, ['REGION_UA', 'region_ua']));
            $district_ua = trim((string)$this->rowValue($row, ['DISTRICT_UA', 'district_ua']));

            if ($district_id === 0 || $district_ua === '') {
                return [];
            }

            return [
                'ukr_poshta_region_id' => $region_id,
                'district_id' => $district_id,
                'region_ua' => $region_ua,
                'district_ua' => $district_ua,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $rows)));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param int                              $district_id
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCityRows(array $rows, int $district_id): array
    {
        return array_values(array_filter(array_map(function (array $row) use ($district_id): array {
            $city_id = (int)$this->rowValue($row, ['CITY_ID', 'city_id']);
            $region_id = (int)$this->rowValue($row, ['REGION_ID', 'region_id']);
            $region_ua = trim((string)$this->rowValue($row, ['REGION_UA', 'region_ua']));
            $district_ua = trim((string)$this->rowValue($row, ['DISTRICT_UA', 'district_ua']));
            $city_ua = trim((string)$this->rowValue($row, ['CITY_UA', 'city_ua']));

            if ($city_id === 0 || $city_ua === '') {
                return [];
            }

            $latitude = (string)$this->rowValue($row, ['LATTITUDE', 'LATITUDE', 'latitude']);
            $longitude = (string)$this->rowValue($row, ['LONGITUDE', 'longitude']);

            return [
                'ukr_poshta_region_id' => $region_id,
                'ukr_poshta_district_id' => $district_id,
                'city_id' => $city_id,
                'description' => trim(sprintf('%s (%s р-н., %s обл.)', $city_ua, $district_ua, $region_ua)),
                'city_ua' => $city_ua,
                'latitude' => $latitude !== '' ? $latitude : null,
                'longitude' => $longitude !== '' ? $longitude : null,
                'region_ua' => $region_ua,
                'district_ua' => $district_ua,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $rows)));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param int                              $district_id
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizePostOfficeRows(array $rows, int $district_id): array
    {
        return array_values(array_filter(array_map(function (array $row) use ($district_id): array {
            $postcode = (int)$this->rowValue($row, ['POSTCODE', 'postcode']);
            $city_id = (int)$this->rowValue($row, ['PDCITY_ID', 'pdcity_id', 'CITY_ID', 'city_id']);
            $region_id = (int)$this->rowValue($row, ['POREGION_ID', 'poregion_id', 'REGION_ID', 'region_id']);
            $city_name = trim((string)$this->rowValue($row, ['PDCITY_UA', 'pdcity_ua', 'CITY_UA', 'city_ua']));
            $region_ua = trim((string)$this->rowValue($row, ['REGION_UA', 'region_ua']));
            $district_ua = trim((string)$this->rowValue($row, ['DISTRICT_UA', 'district_ua']));
            $address = trim((string)$this->rowValue($row, ['ADDRESS', 'address']));
            $postreet_id = trim((string)$this->rowValue($row, ['POSTREET_ID', 'postreet_id']));
            $lock_code = (int)$this->rowValue($row, ['LOCK_CODE', 'lock_code'], 0);
            $latitude = (string)$this->rowValue($row, ['LATTITUDE', 'LATITUDE', 'latitude']);
            $longitude = (string)$this->rowValue($row, ['LONGITUDE', 'longitude']);
            $city_type = trim((string)$this->rowValue($row, ['PDCITYTYPE_UA', 'pdcitytype_ua']));
            $description = trim(sprintf('(%s) %s %s %s', $postcode, $city_type, $city_name, $address));

            if ($postcode === 0 || $city_id === 0) {
                return [];
            }

            return [
                'poregion_id' => $region_id,
                'podistrict_id' => $district_id,
                'pdcity_id' => $city_id,
                'description' => $description,
                'latitude' => $latitude !== '' ? $latitude : null,
                'longitude' => $longitude !== '' ? $longitude : null,
                'lock_code' => $lock_code,
                'postcode' => $postcode,
                'region_ua' => $region_ua,
                'district_ua' => $district_ua,
                'postreet_id' => $postreet_id !== '' ? $postreet_id : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $rows)));
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string>   $keys
     * @param mixed                $default
     *
     * @return mixed
     */
    private function rowValue(array $row, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (Arr::has($row, $key)) {
                return Arr::get($row, $key);
            }
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    private function markQueuedSyncCompleted(array $state): array
    {
        $state['is_running'] = false;
        $state['phase'] = self::PHASE_FINALIZE;
        $state['stage'] = 'completed';
        $state['updated_at'] = now()->toIso8601String();
        $state['message'] = __('admin/modules/ukr_poshta.sync.messages.completed');

        $summary = $this->getLastSyncSummary();
        Cache::put(self::LAST_SYNC_SUMMARY_CACHE_KEY, $summary, now()->addDay());
        $this->saveQueuedSyncState($state);

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    private function markQueuedSyncStopped(array $state): array
    {
        $state['is_running'] = false;
        $state['phase'] = self::PHASE_STOPPED;
        $state['stage'] = 'stopped';
        $state['updated_at'] = now()->toIso8601String();
        $state['message'] = __('admin/modules/ukr_poshta.sync.messages.stopped');

        $this->saveQueuedSyncState($state);

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @param string               $message
     *
     * @return array<string, mixed>
     */
    private function markQueuedSyncFailed(array $state, string $message): array
    {
        Log::channel('stack')->error($message);

        $state['is_running'] = false;
        $state['phase'] = self::PHASE_FAILED;
        $state['stage'] = 'failed';
        $state['updated_at'] = now()->toIso8601String();
        $state['message'] = $message;

        $this->saveQueuedSyncState($state);

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    private function makeInitialQueuedSyncState(): array
    {
        return [
            'is_running' => false,
            'stop_requested' => false,
            'stage' => self::STAGE_REGIONS,
            'phase' => self::PHASE_COLLECT,
            'stage_queue' => [],
            'stage_total_rows' => 0,
            'stage_processed_rows' => 0,
            'overall_progress' => 0,
            'message' => '',
            'region_ids' => [],
            'district_ids' => [],
            'started_at' => null,
            'updated_at' => null,
            'stage_initialized' => false,
            'summary' => $this->makeInitialQueuedSyncSummary(),
        ];
    }

    /**
     * @return array<string, array{processed:int,total:int,imported:int}>
     */
    private function makeInitialQueuedSyncSummary(): array
    {
        return [
            self::STAGE_REGIONS => [
                'processed' => 0,
                'total' => 0,
                'imported' => 0,
            ],
            self::STAGE_DISTRICTS => [
                'processed' => 0,
                'total' => 0,
                'imported' => 0,
            ],
            self::STAGE_CITIES => [
                'processed' => 0,
                'total' => 0,
                'imported' => 0,
            ],
            self::STAGE_POST_OFFICES => [
                'processed' => 0,
                'total' => 0,
                'imported' => 0,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $summary
     *
     * @return array<string, array{processed:int,total:int,imported:int}>
     */
    private function normalizeQueuedSyncSummary(array $summary): array
    {
        return array_replace_recursive($this->makeInitialQueuedSyncSummary(), $summary);
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return void
     */
    private function saveQueuedSyncState(array $state): void
    {
        Cache::put(self::SYNC_STATE_CACHE_KEY, $state, now()->addDay());
    }

    /**
     * @return void
     */
    public function forgetQueuedSyncState(): void
    {
        Cache::forget(self::LAST_SYNC_SUMMARY_CACHE_KEY);
        Cache::forget(self::SYNC_STATE_CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function getLastSyncSummary(): array
    {
        $summary = Cache::get(self::LAST_SYNC_SUMMARY_CACHE_KEY, []);

        return is_array($summary) ? $summary : [];
    }

    private function resolveOverallProgress(string $stage, int $processed_rows, int $total_rows): int
    {
        $stage_weight = match ($stage) {
            self::STAGE_DISTRICTS => 25,
            self::STAGE_CITIES => 50,
            self::STAGE_POST_OFFICES => 75,
            'completed' => 100,
            default => 0,
        };

        if ($total_rows <= 0) {
            return $stage_weight;
        }

        $stage_progress = (int) round((((float) $processed_rows / (float) $total_rows) * (float) 25));

        return min(100, $stage_weight + $stage_progress);
    }
}
