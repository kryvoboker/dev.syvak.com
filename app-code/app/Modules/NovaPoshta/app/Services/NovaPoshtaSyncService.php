<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\NovaPoshta\Models\NovaPoshtaCity;
use Modules\NovaPoshta\Models\NovaPoshtaPoshtomat;
use Modules\NovaPoshta\Models\NovaPoshtaPostOffice;
use Modules\NovaPoshta\Models\NovaPoshtaRegion;
use Modules\NovaPoshta\Support\NovaPoshtaConfig;
use RuntimeException;

class NovaPoshtaSyncService
{
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
        return [
            'regions' => $this->syncRegions(),
            'cities' => $this->syncCities(),
            'post_offices' => $this->syncPostOffices(),
            'poshtomats' => $this->syncPoshtomats(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function syncRegions(): array
    {
        $response = $this->api_service->getRegions();
        $rows = $this->extractRows($response, 'regions');

        $normalized_rows = $this->normalizeRegionRows($rows);

        if ($normalized_rows === []) {
            throw new RuntimeException('Nova Poshta API returned no usable regions.');
        }

        return DB::transaction(function () use ($normalized_rows): array {
            NovaPoshtaRegion::query()->delete();
            NovaPoshtaRegion::query()->insert($normalized_rows);

            return [
                'imported' => count($normalized_rows),
            ];
        });
    }

    /**
     * @return array<string, int>
     */
    public function syncCities(): array
    {
        $response = $this->api_service->getCities(1);
        $rows = $this->extractRows($response, 'cities');
        $total_pages = $this->resolveTotalPages($response);

        for ($page = 2; $page <= $total_pages; $page++) {
            $page_response = $this->api_service->getCities($page);
            $rows = array_merge($rows, $this->extractRows($page_response, 'cities'));
        }

        $region_ids_by_ref = NovaPoshtaRegion::query()->pluck('id', 'ref')->all();
        $normalized_rows = $this->normalizeCityRows($rows, $region_ids_by_ref);

        if ($normalized_rows === []) {
            throw new RuntimeException('Nova Poshta API returned no usable cities.');
        }

        return DB::transaction(function () use ($normalized_rows): array {
            NovaPoshtaCity::query()->delete();
            NovaPoshtaCity::query()->insert($normalized_rows);

            return [
                'imported' => count($normalized_rows),
            ];
        });
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
        if (! (bool) Arr::get($response, 'success', false)) {
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
            array_filter($rows, fn (array $row): bool => filled((string) Arr::get($row, 'Ref', ''))),
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
        $response = $this->api_service->getWarehouses(1);
        $rows = $this->extractRows($response, 'warehouses');
        $total_pages = $this->resolveTotalPages($response);

        for ($page = 2; $page <= $total_pages; $page++) {
            $page_response = $this->api_service->getWarehouses($page);
            $rows = array_merge($rows, $this->extractRows($page_response, 'warehouses'));
        }

        $city_ids_by_ref = NovaPoshtaCity::query()->pluck('id', 'ref')->all();
        $normalized_rows = $this->normalizeWarehouseRows($rows, $city_ids_by_ref, $is_poshtomat);

        if ($normalized_rows === []) {
            throw new RuntimeException(
                $is_poshtomat
                    ? 'Nova Poshta API returned no usable poshtomats.'
                    : 'Nova Poshta API returned no usable post offices.',
            );
        }

        return DB::transaction(function () use ($model_class, $normalized_rows): array {
            $model_class::query()->delete();
            $model_class::query()->insert($normalized_rows);

            return [
                'imported' => count($normalized_rows),
            ];
        });
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
