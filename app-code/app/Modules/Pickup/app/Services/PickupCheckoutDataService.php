<?php

declare(strict_types=1);

namespace Modules\Pickup\Services;

use App\Enums\Order\OrderDataKeyEnum;
use App\Models\ApplicationSettings\Language;
use Illuminate\Support\Facades\Log;
use Modules\Pickup\Support\PickupConfig;

final class PickupCheckoutDataService
{
    public function __construct(
        private readonly PickupConfig $pickup_config,
    ) {
    }

    /**
     * @return array{is_available: bool, delivery_method: string, store_address: string, map_iframe: string}
     */
    public function getCheckoutData(string $locale): array
    {
        if (! is_enabled_singleton_module('Pickup')) {
            return [
                'is_available' => false,
                OrderDataKeyEnum::DeliveryMethod->value => PickupConfig::DELIVERY_METHOD,
                'store_address' => '',
                'map_iframe' => '',
            ];
        }

        $active_language_codes = (new Language())
            ->getActiveLanguages()
            ->pluck('code')
            ->map(fn (mixed $code): string => strtolower((string) $code))
            ->values()
            ->all();
        $safe_map_iframe = $this->pickup_config->getSafeMapIframe();
        $is_complete = $this->pickup_config->isComplete($active_language_codes);

        if (! $is_complete) {
            Log::channel('stack')->warning('[PickupCheckoutDataService.getCheckoutData] configuration incomplete', [
                'active_languages_count' => count($active_language_codes),
            ]);
        }

        return [
            'is_available' => $is_complete,
            OrderDataKeyEnum::DeliveryMethod->value => PickupConfig::DELIVERY_METHOD,
            'store_address' => $is_complete ? $this->pickup_config->getAddressForLocale($locale) : '',
            'map_iframe' => $is_complete ? $safe_map_iframe : '',
        ];
    }
}
