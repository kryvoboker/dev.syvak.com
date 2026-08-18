<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Supports\Services\StorefrontCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CheckoutCitySearchService
{
    private const int RESULT_LIMIT = 100;

    public function __construct(
        private readonly StorefrontCacheService $storefront_cache_service,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function searchCities(string $city_keyword): array
    {
        $search_term = Str::lower(Str::squish($city_keyword));

        if (Str::length($search_term) < 3) {
            return $this->searchCitiesUncached($city_keyword);
        }

        $cache_key = sprintf(
            'checkout:cities:%s:%s:%s',
            md5($search_term),
            is_enabled_singleton_module('NovaPoshta') ? 'nova' : 'no-nova',
            is_enabled_singleton_module('UkrPoshta') ? 'ukr' : 'no-ukr',
        );

        $result = $this->storefront_cache_service->remember(
            $cache_key,
            fn (): array => $this->searchCitiesUncached($city_keyword),
            300,
        );

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * @throws Throwable
     * @return array{
     *     success: bool,
     *     error_message?: string,
     *     cities_data: array<int, array{
     *         nova_poshta_city_id: string|null,
     *         city_description: string,
     *         ukr_poshta_city_id: int|null,
     *         city_lat: float|null,
     *         city_lng: float|null
     *     }>
     * }
     */
    private function searchCitiesUncached(string $city_keyword): array
    {
        $normalized_keyword = Str::squish($city_keyword);
        $search_term = Str::lower($normalized_keyword);
        $min_search_keyword_length = 3;

        if (Str::length($search_term) < $min_search_keyword_length) {
            Log::channel('stack')->warning('[CheckoutCitySearchService.searchCities] invalid search term', [
                'search_term_length' => Str::length($search_term),
            ]);

            return [
                'success' => false,
                'error_message' => "Search term must be at least $min_search_keyword_length characters long.",
                'cities_data' => [],
            ];
        }

        $search_like = "%$search_term%";
        $connection = DB::connection();
        $prefix = $connection->getTablePrefix();
        $is_enabled_nova_poshta = is_enabled_singleton_module('NovaPoshta');
        $is_enabled_ukr_poshta = is_enabled_singleton_module('UkrPoshta');

        if ($is_enabled_nova_poshta === true && $is_enabled_ukr_poshta === true) {
            $cities_data = [];

            $ukr_poshta_sql = '
                SELECT description AS city_description,
                       city_id     AS ukr_poshta_city_id,
                       city_ua 	   AS city_name,
                       region_ua   AS region_name,
                       latitude,
                       longitude
                FROM ' . $prefix . 'ukr_poshta_cities
                WHERE city_ua LIKE ?
                    ORDER BY region_ua, district_ua
                    LIMIT ' . self::RESULT_LIMIT . '
            ';

            $nova_poshta_sql = '
                SELECT ref         		  AS nova_poshta_city_id,
                       description 		  AS city_description,
                       city_name,
                       region_description AS region_name,
                       latitude,
                       longitude
                FROM ' . $prefix . 'nova_poshta_cities
                WHERE city_name LIKE ?
                    ORDER BY region_description
                    LIMIT ' . self::RESULT_LIMIT . '
            ';

            try {
                $ukr_poshta_results = array_values($connection->select($ukr_poshta_sql, [$search_like]));
                /** @var array<int, object> $ukr_poshta_results */
                $nova_poshta_results = array_values($connection->select($nova_poshta_sql, [$search_like]));
                /** @var array<int, object> $nova_poshta_results */
                $ukr_poshta_cities_data = $this->normalizeResults($ukr_poshta_results);
                $nova_poshta_cities_data = $this->normalizeResults($nova_poshta_results);

                if (count($ukr_poshta_cities_data) > count($nova_poshta_cities_data)) {
                    $this->matchAndMergeCities($nova_poshta_cities_data, $ukr_poshta_cities_data, $cities_data);
                } else {
                    $this->matchAndMergeCities($ukr_poshta_cities_data, $nova_poshta_cities_data, $cities_data);
                }

                $nova_poshta_cities_data = array_filter($nova_poshta_cities_data);
                $ukr_poshta_cities_data = array_filter($ukr_poshta_cities_data);

                $cities_data = array_merge(
                    $cities_data,
                    $ukr_poshta_cities_data,
                    $nova_poshta_cities_data,
                );

                $this->sortCities($cities_data);

                return [
                    'success' => empty($cities_data) === false,
                    'cities_data' => $this->normalizeCityResponse($cities_data),
                ];
            } catch (Throwable $throwable) {
                Log::channel('stack')->error('[CheckoutCitySearchService.searchCities] query failed', [
                    'search_term' => $search_term,
                    'exception' => $throwable::class,
                    'message' => $throwable->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error_message' => 'Something went wrong. Please try again later.',
                    'cities_data' => [],
                ];
            }
        } elseif ($is_enabled_nova_poshta === true) {
            $sql = '
				SELECT ref         AS nova_poshta_city_id,
					   description AS city_description,
					   city_name,
					   region_description AS region_name,
					   NULL        AS ukr_poshta_city_id,
					   latitude,
					   longitude
				FROM ' . $prefix . 'nova_poshta_cities
				WHERE city_name LIKE ?
					ORDER BY region_description
					LIMIT ' . self::RESULT_LIMIT . '
			';
        } elseif ($is_enabled_ukr_poshta === true) {
            $sql = '
				SELECT NULL        AS nova_poshta_city_id,
					   description AS city_description,
					   city_ua     AS city_name,
					   region_ua   AS region_name,
					   city_id     AS ukr_poshta_city_id,
					   latitude,
					   longitude
				FROM ' . $prefix . 'ukr_poshta_cities
				WHERE city_ua LIKE ?
					ORDER BY region_ua, district_ua
					LIMIT ' . self::RESULT_LIMIT . '
			';
        } else {
            Log::channel('stack')->error('[CheckoutCitySearchService.searchCities] no modules enabled', [
                'file' => __FILE__,
                'line' => __LINE__,
            ]);

            return [
                'success' => false,
                'error_message' => 'No shipping are found. Please contact us.',
                'cities_data' => [],
            ];
        }

        try {
            $city_results = array_values($connection->select($sql, [$search_like]));
            /** @var array<int, object> $city_results */
            $cities_data = $this->normalizeResults($city_results);

            $this->sortCities($cities_data);

            return [
                'success' => empty($cities_data) === false,
                'cities_data' => $this->normalizeCityResponse($cities_data),
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[CheckoutCitySearchService.searchCities] query failed', [
                'search_term' => $search_term,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return [
                'success' => false,
                'error_message' => 'Something went wrong. Please try again later.',
                'cities_data' => [],
            ];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $cities_data
     *
     * @return void
     */
    private function sortCities(array &$cities_data): void
    {
        usort(
            $cities_data,
            fn ($city_one, $city_two) => strcmp(
                $this->toString($city_one['city_description'] ?? ''),
                $this->toString($city_two['city_description'] ?? ''),
            ),
        );
    }

    /**
     * @param array<int, object> $results
     *
     * @return array<int, array{
     *     city_name: string,
     *     region_name: string,
     *     nova_poshta_city_id: string|null,
     *     ukr_poshta_city_id: int|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     city_description: string
     * }>
     */
    private function normalizeResults(array $results): array
    {
        return array_map(function (object $result): array {
            return [
                'city_name' => $this->toString(data_get($result, 'city_name', '')),
                'region_name' => $this->toString(data_get($result, 'region_name', '')),
                'nova_poshta_city_id' => data_get($result, 'nova_poshta_city_id') !== null
                    ? $this->toString(data_get($result, 'nova_poshta_city_id'))
                    : null,
                'ukr_poshta_city_id' => data_get($result, 'ukr_poshta_city_id') !== null ? $this->toInt(data_get($result, 'ukr_poshta_city_id')) : null,
                'latitude' => data_get($result, 'latitude') !== null ? $this->toFloat(data_get($result, 'latitude')) : null,
                'longitude' => data_get($result, 'longitude') !== null ? $this->toFloat(data_get($result, 'longitude')) : null,
                'city_description' => $this->toString(data_get($result, 'city_description', '')),
            ];
        }, $results);
    }

    /**
     * @param array<int, array<string, mixed>> $cities_data
     * @return array<int, array{nova_poshta_city_id: string|null, city_description: string, ukr_poshta_city_id: int|null, city_lat: float|null, city_lng: float|null}>
     */
    private function normalizeCityResponse(array $cities_data): array
    {
        /** @var array<int, array{nova_poshta_city_id: string|null, city_description: string, ukr_poshta_city_id: int|null, city_lat: float|null, city_lng: float|null}> $response */
        $response = array_map(static fn (array $city): array => [
            'nova_poshta_city_id' => isset($city['nova_poshta_city_id']) ? self::scalarString($city['nova_poshta_city_id']) : null,
            'city_description' => self::scalarString($city['city_description'] ?? ''),
            'ukr_poshta_city_id' => isset($city['ukr_poshta_city_id']) ? self::scalarInt($city['ukr_poshta_city_id']) : null,
            'city_lat' => isset($city['city_lat']) ? self::scalarFloat($city['city_lat']) : (isset($city['latitude']) ? self::scalarFloat($city['latitude']) : null),
            'city_lng' => isset($city['city_lng']) ? self::scalarFloat($city['city_lng']) : (isset($city['longitude']) ? self::scalarFloat($city['longitude']) : null),
        ], array_values($cities_data));

        return $response;
    }

    /**
     * @param array<int, array<string, mixed>> $primary_cities
     * @param array<int, array<string, mixed>> $secondary_cities
     * @param array<int, array<string, mixed>> $cities_data
     *
     * @return void
     */
    private function matchAndMergeCities(array &$primary_cities, array &$secondary_cities, array &$cities_data): void
    {
        foreach ($primary_cities as $primary_index => $primary_city) {
            if (empty($secondary_cities)) {
                break;
            }

            $primary_city_name = Str::trim(Str::lower($this->toString($primary_city['city_name'] ?? '')));
            $primary_region_name = Str::trim(Str::lower($this->toString($primary_city['region_name'] ?? '')));

            foreach ($secondary_cities as $secondary_index => $secondary_city_data) {
                $secondary_city_name = Str::trim(Str::lower($this->toString($secondary_city_data['city_name'] ?? '')));
                $secondary_region_name = Str::trim(Str::lower($this->toString($secondary_city_data['region_name'] ?? '')));

                $cities_match = $primary_city_name === $secondary_city_name;
                $regions_match = $secondary_region_name === $primary_region_name
                    || $secondary_region_name === Str::lower($this->toString(config('shipping.ukraine_capital_uk_name')));

                if ($cities_match && $regions_match) {
                    // Determine which data is Nova Poshta and which is Ukr Poshta
                    $is_primary_nova_poshta = isset($primary_city['nova_poshta_city_id']);

                    $cities_data[] = [
                        'nova_poshta_city_id' => $is_primary_nova_poshta
                            ? $primary_city['nova_poshta_city_id']
                            : $secondary_city_data['nova_poshta_city_id'],
                        'city_description' => $is_primary_nova_poshta
                            ? $secondary_city_data['city_description']
                            : $primary_city['city_description'],
                        'ukr_poshta_city_id' => $is_primary_nova_poshta
                            ? $secondary_city_data['ukr_poshta_city_id']
                            : $primary_city['ukr_poshta_city_id'],
                        'city_lat' => $primary_city['latitude'] ?? $secondary_city_data['latitude'],
                        'city_lng' => $primary_city['longitude'] ?? $secondary_city_data['longitude'],
                    ];

                    unset($primary_cities[$primary_index]);
                    unset($secondary_cities[$secondary_index]);

                    $secondary_cities = array_filter($secondary_cities);

                    continue 2;
                }
            }
        }
    }

    private function toString(mixed $value): string
    {
        return self::scalarString($value);
    }

    private static function scalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function toInt(mixed $value): int
    {
        return self::scalarInt($value);
    }

    private static function scalarInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function toFloat(mixed $value): float
    {
        return self::scalarFloat($value);
    }

    private static function scalarFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
