<?php

declare(strict_types=1);

namespace Modules\Pickup\Services\Filament;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Str;

final class PickupIframeSanitizer
{
    /**
     * @return array{value: string, errors: array<int, string>}
     */
    public function sanitize(string $html): array
    {
        $html = trim($html);

        if ($html === '') {
            return ['value' => '', 'errors' => []];
        }

        $previous_errors = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous_errors);

        if ($loaded === false) {
            return ['value' => '', 'errors' => ['invalid_html']];
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return ['value' => '', 'errors' => ['iframe_only']];
        }

        $iframes = $body->getElementsByTagName('iframe');

        if ($iframes->length !== 1 || $body->childNodes->length !== 1) {
            return ['value' => '', 'errors' => ['iframe_only']];
        }

        $iframe = $iframes->item(0);

        if (! $iframe instanceof DOMElement) {
            return ['value' => '', 'errors' => ['invalid_iframe']];
        }

        $source = trim((string) $iframe->getAttribute('src'));

        if (! $this->isAllowedSource($source)) {
            return ['value' => '', 'errors' => ['invalid_source']];
        }

        $attributes = [
            'src' => $source,
            'title' => trim((string) $iframe->getAttribute('title')) ?: 'Google Maps',
            'loading' => 'lazy',
            'referrerpolicy' => trim((string) $iframe->getAttribute('referrerpolicy')) ?: 'no-referrer-when-downgrade',
            'allowfullscreen' => 'true',
        ];

        $allow = trim((string) $iframe->getAttribute('allow'));

        if ($allow !== '') {
            $attributes['allow'] = Str::of($allow)
                ->explode(';')
                ->map(fn (string $value): string => trim($value))
                ->filter(fn (string $value): bool => $value !== '')
                ->implode('; ');
        }

        $serialized_attributes = collect($attributes)
            ->map(fn (string $value, string $attribute): string => sprintf('%s="%s"', $attribute, e($value, false)))
            ->implode(' ');

        return [
            'value' => '<iframe ' . $serialized_attributes . '></iframe>',
            'errors' => [],
        ];
    }

    private function isAllowedSource(string $source): bool
    {
        $url = parse_url($source);

        if (($url['scheme'] ?? '') !== 'https' || ! isset($url['host'])) {
            return false;
        }

        $host = Str::lower((string) $url['host']);
        $path = (string) ($url['path'] ?? '');

        return in_array($host, ['google.com', 'www.google.com', 'maps.google.com'], true)
            && Str::startsWith($path, '/maps/embed');
    }
}
