<?php

declare(strict_types=1);

namespace Modules\Pickup\Support;

use App\Enums\Order\DeliveryMethodEnum;
use Illuminate\Support\Str;
use Modules\Pickup\Services\Filament\PickupIframeSanitizer;

final class PickupConfig
{
    public const string ADDRESSES_GLOBAL_CONFIG_KEY = 'pickup_store.addresses';

    public const string MAP_IFRAME_GLOBAL_CONFIG_KEY = 'pickup_store.map_iframe';

    public const string DELIVERY_METHOD = DeliveryMethodEnum::PickupStore->value;

    public function __construct(
        private readonly PickupIframeSanitizer $iframe_sanitizer,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getAddresses(): array
    {
        $value = get_global_config(self::ADDRESSES_GLOBAL_CONFIG_KEY, []);

        if (is_array($value)) {
            return $this->normalizeAddresses($value);
        }

        $decoded_value = json_decode(is_scalar($value) ? (string) $value : '', true);

        return is_array($decoded_value) ? $this->normalizeAddresses($decoded_value) : [];
    }

    public function getAddressForLocale(string $locale): string
    {
        return $this->getAddresses()[$locale] ?? '';
    }

    public function getMapIframe(): string
    {
        $value = get_global_config(self::MAP_IFRAME_GLOBAL_CONFIG_KEY, '');

        return trim(is_scalar($value) ? (string) $value : '');
    }

    public function getSafeMapIframe(): string
    {
        return $this->iframe_sanitizer->sanitize($this->getMapIframe())['value'];
    }

    /**
     * @param  array<int, string>  $active_language_codes
     */
    public function isComplete(array $active_language_codes): bool
    {
        if ($active_language_codes === []) {
            return false;
        }

        $addresses = $this->getAddresses();

        foreach ($active_language_codes as $language_code) {
            if (blank($addresses[$language_code] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int|string, mixed>  $addresses
     * @return array<string, string>
     */
    private function normalizeAddresses(array $addresses): array
    {
        $normalized_addresses = [];

        foreach ($addresses as $language_code => $address) {
            if (! is_string($language_code) || ! is_scalar($address)) {
                continue;
            }

            $normalized_language_code = Str::lower(trim($language_code));
            $normalized_address = Str::squish((string) $address);

            if ($normalized_language_code !== '' && $normalized_address !== '') {
                $normalized_addresses[$normalized_language_code] = $normalized_address;
            }
        }

        return $normalized_addresses;
    }
}
