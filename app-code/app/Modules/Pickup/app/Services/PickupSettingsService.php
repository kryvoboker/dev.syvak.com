<?php

declare(strict_types=1);

namespace Modules\Pickup\Services;

use Illuminate\Support\Facades\Log;
use Modules\Pickup\Support\PickupConfig;
use Throwable;

final class PickupSettingsService
{
    public function __construct(
        private readonly PickupIframeSanitizer $iframe_sanitizer,
    ) {
    }

    /**
     * @param  array<string, string>  $addresses
     * @throws Throwable
     * @return array{addresses: array<string, string>, map_iframe: string}
     */
    public function save(array $addresses, string $map_iframe): array
    {
        $sanitized_iframe = $this->iframe_sanitizer->sanitize($map_iframe);

        if ($sanitized_iframe['errors'] !== []) {
            Log::channel('stack')->warning('[PickupSettingsService.save] iframe rejected', [
                'errors' => $sanitized_iframe['errors'],
            ]);

            throw new \InvalidArgumentException('The Google Maps iframe is not safe or valid.');
        }

        $normalized_addresses = collect($addresses)
            ->mapWithKeys(fn (mixed $address, mixed $language_code): array => [
                strtolower(trim((string) $language_code)) => trim((string) $address),
            ])
            ->filter(fn (string $address, string $language_code): bool => $language_code !== '' && $address !== '')
            ->all();

        set_global_config(PickupConfig::ADDRESSES_GLOBAL_CONFIG_KEY, $normalized_addresses);
        set_global_config(PickupConfig::MAP_IFRAME_GLOBAL_CONFIG_KEY, $sanitized_iframe['value']);

        Log::channel('daily')->info('[PickupSettingsService.save] settings saved', [
            'languages_count' => count($normalized_addresses),
        ]);

        return [
            'addresses' => $normalized_addresses,
            'map_iframe' => $sanitized_iframe['value'],
        ];
    }
}
