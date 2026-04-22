<?php

declare(strict_types=1);

namespace App\Services\Trait;

use Illuminate\Support\Collection as SupportCollection;

trait SocialServiceTrait
{
    /**
     * @param mixed  $url
     * @param string $locale
     *
     * @return string
     */
    protected function normalizeSocialUrl(mixed $url, string $locale): string
    {
        if (is_string($url)) {
            return trim($url);
        }

        if ($url instanceof SupportCollection) {
            return $this->normalizeSocialUrl($url->toArray(), $locale);
        }

        if (!is_array($url)) {
            return '';
        }

        $localized_value = data_get($url, $locale);

        if (is_string($localized_value) && filled(trim($localized_value))) {
            return trim($localized_value);
        }

        $first_valid_url = collect($url)
            ->first(fn(mixed $value): bool => is_string($value) && filled(trim($value)));

        return is_string($first_valid_url) ? trim($first_valid_url) : '';
    }
}
