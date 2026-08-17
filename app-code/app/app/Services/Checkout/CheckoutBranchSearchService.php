<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Enums\Order\DeliveryMethodEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\NovaPoshta\Models\NovaPoshtaPoshtomat;
use Modules\NovaPoshta\Models\NovaPoshtaPostOffice;
use Modules\NovaPoshta\Services\ShippingScheduleFormatter;
use Modules\UkrPoshta\Models\UkrPoshtaPostOffice;
use Throwable;

class CheckoutBranchSearchService
{
    /**
     * @param array<string, mixed> $city
     *
     * @return array{
     *     success: bool,
     *     error_message?: string,
     *     items: array<int, array<string, mixed>>
     * }
     */
    public function loadBranches(string $delivery_method, array $city): array
    {
        $normalized_delivery_method = Str::lower(Str::squish($delivery_method));

        return match ($normalized_delivery_method) {
            DeliveryMethodEnum::NovaPoshta->value => $this->loadNovaPoshtaBranches($city),
            DeliveryMethodEnum::NovaPoshtaPoshtomat->value => $this->loadNovaPoshtaPoshtomats($city),
            DeliveryMethodEnum::UkrPoshta->value => $this->loadUkrPoshtaBranches($city),
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
    private function loadNovaPoshtaBranches(array $city): array
    {
        if (is_enabled_singleton_module('NovaPoshta') === false) {
            return [
                'success' => false,
                'error_message' => 'Nova Poshta module is disabled.',
                'items' => [],
            ];
        }

        $city_ref = Str::squish((string)Arr::get($city, 'nova_poshta_city_id', ''));

        if ($city_ref === '') {
            return [
                'success' => false,
                'error_message' => 'City is required for Nova Poshta branch search.',
                'items' => [],
            ];
        }

        try {
            $rows = NovaPoshtaPostOffice::query()
                ->where('city_ref', $city_ref)
                ->orderBy('description')
                ->get();

            return [
                'success' => $rows->isNotEmpty(),
                'items' => $this->normalizeNovaPoshtaRows($rows->toArray(), 'nova_poshta'),
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[CheckoutBranchSearchService.loadNovaPoshtaBranches] query failed', [
                'city_ref' => $city_ref,
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
    private function loadNovaPoshtaPoshtomats(array $city): array
    {
        if (is_enabled_singleton_module('NovaPoshta') === false) {
            return [
                'success' => false,
                'error_message' => 'Nova Poshta module is disabled.',
                'items' => [],
            ];
        }

        $city_ref = Str::squish((string)Arr::get($city, 'nova_poshta_city_id', ''));

        if ($city_ref === '') {
            return [
                'success' => false,
                'error_message' => 'City is required for Nova Poshta poshtomat search.',
                'items' => [],
            ];
        }

        try {
            $rows = NovaPoshtaPoshtomat::query()
                ->where('city_ref', $city_ref)
                ->orderBy('description')
                ->get();

            return [
                'success' => $rows->isNotEmpty(),
                'items' => $this->normalizeNovaPoshtaRows($rows->toArray(), 'nova_poshta_poshtomat'),
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[CheckoutBranchSearchService.loadNovaPoshtaPoshtomats] query failed', [
                'city_ref' => $city_ref,
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
    private function loadUkrPoshtaBranches(array $city): array
    {
        if (is_enabled_singleton_module('UkrPoshta') === false) {
            return [
                'success' => false,
                'error_message' => 'Ukr Poshta module is disabled.',
                'items' => [],
            ];
        }

        $city_id = (int)Arr::get($city, 'ukr_poshta_city_id', 0);

        if ($city_id <= 0) {
            return [
                'success' => false,
                'error_message' => 'City is required for Ukr Poshta branch search.',
                'items' => [],
            ];
        }

        try {
            $rows = UkrPoshtaPostOffice::query()
                ->where('pdcity_id', $city_id)
                ->where('lock_code', 0)
                ->orderBy('postcode')
                ->get();

            return [
                'success' => $rows->isNotEmpty(),
                'items' => $this->normalizeUkrPoshtaRows($rows),
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[CheckoutBranchSearchService.loadUkrPoshtaBranches] query failed', [
                'city_id' => $city_id,
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
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeNovaPoshtaRows(array $rows, string $delivery_method): array
    {
        $normalized_rows = [];

        $weekday_map = [
            'Monday' => __('novaposhta::storefront/checkout.texts.monday'),
            'Tuesday' => __('novaposhta::storefront/checkout.texts.tuesday'),
            'Wednesday' => __('novaposhta::storefront/checkout.texts.wednesday'),
            'Thursday' => __('novaposhta::storefront/checkout.texts.thursday'),
            'Friday' => __('novaposhta::storefront/checkout.texts.friday'),
            'Saturday' => __('novaposhta::storefront/checkout.texts.saturday'),
            'Sunday' => __('novaposhta::storefront/checkout.texts.sunday'),
        ];

        $text_day_off = __('novaposhta::storefront/checkout.texts.day_off');
        $text_work_schedule = __('novaposhta::storefront/checkout.texts.work_schedule');

        ShippingScheduleFormatter::setWeekdayMap($weekday_map);

        foreach ($rows as $post_office) {
            if (isset($post_office['schedule'])) {
                $post_office['schedule'] = ShippingScheduleFormatter::formatSchedule(
                    $post_office['schedule'],
                    $text_day_off,
                    $text_work_schedule,
                );
            } else {
                $post_office['schedule'] = null;
            }

            $normalized_rows[] = array_filter([
                'id' => $post_office['id'] ?? null,
                'branch_value' => (string)($post_office['id'] ?? ''),
                'delivery_method' => $delivery_method,
                'description' => $post_office['description'] ?? null,
                'label' => trim($post_office['description'] ?? ''),
                'ref' => $post_office['ref'] ?? null,
                'city_ref' => $post_office['city_ref'] ?? null,
                'city_description' => $post_office['city_description'] ?? null,
                'number' => $post_office['number'] ?? null,
                'site_key' => $post_office['site_key'] ?? null,
                'latitude' => $post_office['latitude'] ?? null,
                'longitude' => $post_office['longitude'] ?? null,
                'schedule' => $post_office['schedule'],
            ], static fn (mixed $value): bool => !is_null($value) && $value !== '');
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
            if (!is_array($post_office)) {
                continue;
            }

            $normalized_rows[] = array_filter([
                'id' => $post_office['id'] ?? null,
                'branch_value' => (string)($post_office['id'] ?? ''),
                'delivery_method' => 'ukr_poshta',
                'description' => $post_office['description'] ?? null,
                'label' => trim($post_office['description'] ?? ''),
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
            ], static fn (mixed $value): bool => !is_null($value) && $value !== '');
        }

        return $normalized_rows;
    }
}
