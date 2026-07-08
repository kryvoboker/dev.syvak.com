<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use stdClass;
use Throwable;

class CheckoutCitySearchService
{
    private const int RESULT_LIMIT = 100;

    /**
     * @throws Throwable
     * @return array{
     *     success: bool,
     *     error_message?: string,
     *     cities_data: array<int, array{
     *         nova_poshta_city_id: int|null,
     *         city_description: string,
     *         ukr_poshta_city_id: int|null,
     *         city_lat: float|null,
     *         city_lng: float|null
     *     }>
     * }
     */
    public function searchCities(string $city_keyword): array
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
                $ukr_poshta_cities_data = $this->normalizeResults($connection->select($ukr_poshta_sql, [$search_like]));
                $nova_poshta_cities_data = $this->normalizeResults($connection->select($nova_poshta_sql, [$search_like]));

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
                    'cities_data' => $cities_data,
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
            $cities_data = $this->normalizeResults($connection->select($sql, [$search_like]));

            $this->sortCities($cities_data);

            return [
                'success' => empty($cities_data) === false,
                'cities_data' => $cities_data,
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
     * @param array $cities_data
     *
     * @return void
     */
    private function sortCities(array &$cities_data): void
    {
        usort(
            $cities_data,
            fn ($city_one, $city_two) => strcmp(
                (string)$city_one['city_description'],
                (string)$city_two['city_description'],
            ),
        );
    }

    /**
     * @param array $results
     *
     * @return array<int, array{
     *     city_name: string,
     *     region_name: string,
     *     nova_poshta_city_id: int|null,
     *     ukr_poshta_city_id: int|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     city_description: string
     * }>
     */
    private function normalizeResults(array $results): array
    {
        return array_map(function (stdClass $result) {
            $data = [
                'city_name' => $result->city_name,
                'region_name' => $result->region_name,
                'nova_poshta_city_id' => $result->nova_poshta_city_id ?? null,
                'ukr_poshta_city_id' => $result->ukr_poshta_city_id ?? null,
                'latitude' => $result->latitude,
                'longitude' => $result->longitude,
                'city_description' => $result->city_description,
            ];

            return array_filter($data);
        }, $results);
    }

    /**
     * @param array $primary_cities
     * @param array $secondary_cities
     * @param array $cities_data
     *
     * @return void
     */
    private function matchAndMergeCities(array &$primary_cities, array &$secondary_cities, array &$cities_data): void
    {
        foreach ($primary_cities as $primary_index => $primary_city) {
            if (empty($secondary_cities)) {
                break;
            }

            $primary_city_name = Str::trim(Str::lower((string)$primary_city['city_name']));
            $primary_region_name = Str::trim(Str::lower((string)$primary_city['region_name']));

            foreach ($secondary_cities as $secondary_index => $secondary_city_data) {
                $secondary_city_name = Str::trim(Str::lower((string)$secondary_city_data['city_name']));
                $secondary_region_name = Str::trim(Str::lower((string)$secondary_city_data['region_name']));

                $cities_match = $primary_city_name === $secondary_city_name;
                $regions_match = $secondary_region_name === $primary_region_name
                    || $secondary_region_name === Str::lower(config('shipping.ukraine_capital_uk_name'));

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
}
