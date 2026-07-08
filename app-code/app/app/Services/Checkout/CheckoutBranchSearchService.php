<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\NovaPoshta\Models\NovaPoshtaPostOffice;
use Modules\UkrPoshta\Models\UkrPoshtaPostOffice;
use Throwable;

class CheckoutBranchSearchService
{
    private const int RESULT_LIMIT = 100;

    /**
     * @param array<string, mixed> $city
     *
     * @return array{
     *     success: bool,
     *     error_message?: string,
     *     items: array<int, array<string, mixed>>
     * }
     */
    public function searchBranches(string $delivery_method, array $city, string $branch_keyword): array
    {
        $normalized_delivery_method = Str::lower(Str::squish($delivery_method));
        $normalized_keyword = Str::squish($branch_keyword);

        if ($normalized_keyword === '') {
            return [
                'success' => false,
                'error_message' => 'Branch search term is required.',
                'items' => [],
            ];
        }

        return match ($normalized_delivery_method) {
            'nova_poshta' => $this->searchNovaPoshtaBranches($city, $normalized_keyword),
            'ukr_poshta' => $this->searchUkrPoshtaBranches($city, $normalized_keyword),
            default => [
                'success' => false,
                'error_message' => 'Unsupported delivery method.',
                'items' => [],
            ],
        };
    }

    /**
     * @param array<string, mixed> $city
     *
     * @return array{
     *     success: bool,
     *     error_message?: string,
     *     items: array<int, array<string, mixed>>
     * }
     */
    private function searchNovaPoshtaBranches(array $city, string $branch_keyword): array
    {
        if (is_enabled_singleton_module('NovaPoshta') === false) {
            return [
                'success' => false,
                'error_message' => 'Nova Poshta module is disabled.',
                'items' => [],
            ];
        }

        $city_ref = Str::squish((string) Arr::get($city, 'nova_poshta_city_id', ''));

        if ($city_ref === '') {
            return [
                'success' => false,
                'error_message' => 'City is required for Nova Poshta branch search.',
                'items' => [],
            ];
        }

        try {
            $search_like = '%' . Str::lower($branch_keyword) . '%';

            $rows = NovaPoshtaPostOffice::query()
                ->where('city_ref', $city_ref)
                ->where(function ($query) use ($search_like): void {
                    $query->where('description', 'like', $search_like)
                        ->orWhereRaw('CAST(number AS CHAR) LIKE ?', [$search_like]);
                })
                ->orderBy('description')
                ->limit(self::RESULT_LIMIT)
                ->get();

            return [
                'success' => $rows->isNotEmpty(),
                'items' => $this->normalizeNovaPoshtaRows($rows),
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[CheckoutBranchSearchService.searchNovaPoshtaBranches] query failed', [
                'city_ref' => $city_ref,
                'branch_keyword' => $branch_keyword,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return [
                'success' => false,
                'error_message' => 'Something went wrong. Please try again later.',
                'items' => [],
            ];
        }
    }

    /**
     * @param array<string, mixed> $city
     *
     * @return array{
     *     success: bool,
     *     error_message?: string,
     *     items: array<int, array<string, mixed>>
     * }
     */
    private function searchUkrPoshtaBranches(array $city, string $branch_keyword): array
    {
        if (is_enabled_singleton_module('UkrPoshta') === false) {
            return [
                'success' => false,
                'error_message' => 'Ukr Poshta module is disabled.',
                'items' => [],
            ];
        }

        $city_id = (int) Arr::get($city, 'ukr_poshta_city_id', 0);

        if ($city_id <= 0) {
            return [
                'success' => false,
                'error_message' => 'City is required for Ukr Poshta branch search.',
                'items' => [],
            ];
        }

        try {
            $search_like = '%' . Str::lower($branch_keyword) . '%';

            $rows = UkrPoshtaPostOffice::query()
                ->where('pdcity_id', $city_id)
                ->where('lock_code', 0)
                ->where(function ($query) use ($search_like): void {
                    $query->where('description', 'like', $search_like)
                        ->orWhereRaw('CAST(postcode AS CHAR) LIKE ?', [$search_like]);
                })
                ->orderBy('postcode')
                ->limit(self::RESULT_LIMIT)
                ->get();

            return [
                'success' => $rows->isNotEmpty(),
                'items' => $this->normalizeUkrPoshtaRows($rows),
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[CheckoutBranchSearchService.searchUkrPoshtaBranches] query failed', [
                'city_id' => $city_id,
                'branch_keyword' => $branch_keyword,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return [
                'success' => false,
                'error_message' => 'Something went wrong. Please try again later.',
                'items' => [],
            ];
        }
    }

    /**
     * @param Collection<int, NovaPoshtaPostOffice> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeNovaPoshtaRows(Collection $rows): array
    {
        $normalized_rows = [];

        foreach ($rows->toArray() as $post_office) {
            if (! is_array($post_office)) {
                continue;
            }

            $normalized_rows[] = array_filter([
                'id' => $post_office['id'] ?? null,
                'branch_value' => (string) ($post_office['id'] ?? ''),
                'delivery_method' => 'nova_poshta',
                'description' => $post_office['description'] ?? null,
                'label' => trim((string) ($post_office['description'] ?? '') . (filled($post_office['number'] ?? null) ? ' #' . (string) ($post_office['number'] ?? '') : '')),
                'ref' => $post_office['ref'] ?? null,
                'city_ref' => $post_office['city_ref'] ?? null,
                'city_description' => $post_office['city_description'] ?? null,
                'number' => $post_office['number'] ?? null,
                'site_key' => $post_office['site_key'] ?? null,
                'latitude' => $post_office['latitude'] ?? null,
                'longitude' => $post_office['longitude'] ?? null,
                'schedule' => $post_office['schedule'] ?? null,
            ], static fn (mixed $value): bool => ! is_null($value) && $value !== '');
        }

        return $normalized_rows;
    }

    /**
     * @param Collection<int, UkrPoshtaPostOffice> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeUkrPoshtaRows(Collection $rows): array
    {
        $normalized_rows = [];

        foreach ($rows->toArray() as $post_office) {
            if (! is_array($post_office)) {
                continue;
            }

            $normalized_rows[] = array_filter([
                'id' => $post_office['id'] ?? null,
                'branch_value' => (string) ($post_office['id'] ?? ''),
                'delivery_method' => 'ukr_poshta',
                'description' => $post_office['description'] ?? null,
                'label' => trim((string) ($post_office['description'] ?? '') . (filled($post_office['postcode'] ?? null) ? ' #' . (string) ($post_office['postcode'] ?? '') : '')),
                'poregion_id' => $post_office['poregion_id'] ?? null,
                'podistrict_id' => $post_office['podistrict_id'] ?? null,
                'pdcity_id' => $post_office['pdcity_id'] ?? null,
                'postcode' => $post_office['postcode'] ?? null,
                'region_ua' => $post_office['region_ua'] ?? null,
                'district_ua' => $post_office['district_ua'] ?? null,
                'postreet_id' => $post_office['postreet_id'] ?? null,
                'latitude' => $post_office['latitude'] ?? null,
                'longitude' => $post_office['longitude'] ?? null,
                'lock_code' => $post_office['lock_code'] ?? null,
            ], static fn (mixed $value): bool => ! is_null($value) && $value !== '');
        }

        return $normalized_rows;
    }
}
