<?php

declare(strict_types=1);

namespace Modules\Carousel\Services;

use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Resolves storefront-ready Carousel payload for the requested placement/page type.
 */
class CarouselModuleDataService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolveForPlacement(string $placement, ?string $page_type = null): array
    {
        /** @var Collection<int, ModuleDefinition> $definitions */
        $definitions = resolve_modules_for_context($placement);

        $carousel_modules = $definitions
            ->filter(fn(ModuleDefinition $definition): bool => $definition->nwidart_name === 'Carousel')
            ->map(fn(ModuleDefinition $definition): array => $this->mapDefinitionInstances($definition, $page_type))
            ->collapse()
            ->values();

        /** @var array<int, array<string, mixed>> $resolved_modules */
        $resolved_modules = $carousel_modules->all();

        return $resolved_modules;
    }

    /**
     * @return array<int, array{
     *     instance_id: int,
     *     name: string,
     *     placement: string|null,
     *     page_types: array<int, string>,
     *     open_links_in_new_tab: bool,
     *     slides: non-empty-array<int, non-empty-array<string, mixed>>
     * }>
     */
    private function mapDefinitionInstances(ModuleDefinition $definition, ?string $page_type): array
    {
        /** @var Collection<int, ModuleInstance> $instances */
        $instances = $definition->instances;

        return $instances
            ->filter(fn(ModuleInstance $instance): bool => $this->matchesPageType($instance, $page_type))
            ->map(function (ModuleInstance $instance): array {
                $instance_settings = is_array($instance->settings) ? $instance->settings : [];
                $shared_settings   = Arr::get($instance_settings, 'shared', []);
                $page_types        = collect(Arr::get($shared_settings, 'page_types', []))
                    ->filter(fn(mixed $item): bool => is_string($item) && filled($item))
                    ->values()
                    ->all();
                $slides            = collect(Arr::get($instance_settings, 'slides', []))
                    ->filter(fn(mixed $slide): bool => is_array($slide) && Arr::get($slide, 'is_active', true))
                    ->sortBy(fn(array $slide): int => (int)Arr::get($slide, 'sort_order', 0))
                    ->values()
                    ->map(fn(array $slide): array => $this->mapSlide($slide, $shared_settings))
                    ->filter(fn(array $slide): bool => $slide !== [])
                    ->values()
                    ->all();

                return [
                    'instance_id'           => $instance->id,
                    'name'                  => $instance->name,
                    'placement'             => $instance->placement,
                    'page_types'            => $page_types,
                    'open_links_in_new_tab' => (bool)Arr::get($shared_settings, 'open_links_in_new_tab', true),
                    'slides'                => $slides,
                    'desctop_max_width'     => (int)$shared_settings['desktop_image']['max_width'],
                    'desctop_max_height'    => (int)$shared_settings['desktop_image']['max_height'],
                    'mobile_max_width'      => (int)$shared_settings['mobile_image']['max_width'],
                    'mobile_max_height'     => (int)$shared_settings['mobile_image']['max_height'],
                ];
            })
            ->filter(fn(array $module_data): bool => $module_data['slides'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $slide
     * @param array<string, mixed> $shared_settings
     *
     * @return array<string, mixed>
     */
    private function mapSlide(array $slide, array $shared_settings): array
    {
        $translations                = Arr::get($slide, 'translations', []);
        $current_locale              = app()->getLocale();
        $primary_translation_payload = $this->resolvePrimaryTranslation($translations, $current_locale);
        $translation                 = $primary_translation_payload['translation'];

        if (!is_array($translation)) {
            Log::channel('stack')->warning('Carousel slide skipped because translation payload is invalid.', [
                'locale' => $current_locale,
            ]);

            return [];
        }

        $desktop_image_settings = $this->normalizeImageSizeSettings(Arr::get($shared_settings, 'desktop_image', []));
        $mobile_image_settings  = $this->normalizeImageSizeSettings(Arr::get($shared_settings, 'mobile_image', []));

        $desktop_width              = $desktop_image_settings['width'];
        $desktop_height             = $desktop_image_settings['height'];
        $mobile_width               = $mobile_image_settings['width'];
        $mobile_height              = $mobile_image_settings['height'];
        $price                      = $this->normalizePrice(Arr::get($translation, 'price'));
        $desktop_image_path_payload = $this->resolveTranslationImagePath($translations, 'desktop_image', $current_locale);
        $mobile_image_path_payload  = $this->resolveTranslationImagePath($translations, 'mobile_image', $current_locale);

        return [
            'title'           => (string)Arr::get($translation, 'title', ''),
            'description'     => (string)Arr::get($translation, 'description', ''),
            'button_text'     => (string)Arr::get($translation, 'button_text', ''),
            'button_url'      => sanitaze_url((string)Arr::get($translation, 'button_url', '')),
            'image_url'       => sanitaze_url((string)Arr::get($translation, 'image_url', '')),
            'price'           => $price,
            'formatted_price' => $price !== null ? format_price($price) : null,
            'sort_order'      => (int)Arr::get($slide, 'sort_order', 0),
            'desktop_image'   => $this->buildImagePayload(
                $desktop_image_path_payload['path'],
                $desktop_width,
                $desktop_height
            ),
            'mobile_image'    => $this->buildImagePayload(
                $mobile_image_path_payload['path'],
                $mobile_width,
                $mobile_height
            ),
        ];
    }

    /**
     * @return array{locale: string|null, translation: array<string, mixed>|null}
     */
    private function resolvePrimaryTranslation(mixed $translations, string $current_locale): array
    {
        if (!is_array($translations)) {
            return [
                'locale'      => null,
                'translation' => null,
            ];
        }

        $current_translation = Arr::get($translations, $current_locale);

        if (is_array($current_translation)) {
            return [
                'locale'      => $current_locale,
                'translation' => $current_translation,
            ];
        }

        foreach ($translations as $locale => $translation) {
            if (is_array($translation)) {
                return [
                    'locale'      => is_string($locale) ? $locale : null,
                    'translation' => $translation,
                ];
            }
        }

        return [
            'locale'      => null,
            'translation' => null,
        ];
    }

    /**
     * @return array{path: string|null, locale: string|null}
     */
    private function resolveTranslationImagePath(mixed $translations, string $image_field, string $current_locale): array
    {
        if (!is_array($translations)) {
            return [
                'path'   => null,
                'locale' => null,
            ];
        }

        $current_translation = Arr::get($translations, $current_locale, []);
        $current_path        = is_array($current_translation)
            ? Str::trim((string)Arr::get($current_translation, $image_field, ''))
            : '';

        if (filled($current_path)) {
            return [
                'path'   => $current_path,
                'locale' => $current_locale,
            ];
        }

        foreach ($translations as $locale => $translation) {
            if (!is_array($translation)) {
                continue;
            }

            $candidate_path = Str::trim((string)Arr::get($translation, $image_field, ''));

            if (filled($candidate_path)) {
                return [
                    'path'   => $candidate_path,
                    'locale' => is_string($locale) ? $locale : null,
                ];
            }
        }

        return [
            'path'   => null,
            'locale' => null,
        ];
    }

    /**
     * @return array{urls: array<string, string>, width: int, height: int}
     */
    private function buildImagePayload(?string $image_path, int $width, int $height): array
    {
        if (blank($image_path)) {
            return [
                'urls'   => [],
                'width'  => $width,
                'height' => $height,
            ];
        }

        return [
            'urls'   => multiple_convert_img_and_get_url(
                $image_path,
                $width,
                $height,
                is_square: false,
            ),
            'width'  => $width,
            'height' => $height,
        ];
    }

    private function matchesPageType(ModuleInstance $instance, ?string $page_type): bool
    {
        if (blank($page_type)) {
            return true;
        }

        $instance_settings = is_array($instance->settings) ? $instance->settings : [];
        $page_types        = collect(Arr::get($instance_settings, 'shared.page_types', []));

        if ($page_types->isEmpty()) {
            return true;
        }

        return $page_types->contains($page_type);
    }

    /**
     * Normalizes a mixed slide price value to float when possible.
     */
    private function normalizePrice(mixed $price): ?float
    {
        if (is_int($price) || is_float($price)) {
            return (float)$price;
        }

        if (!is_string($price)) {
            return null;
        }

        $normalized_price = Str::of($price)
            ->trim()
            ->replace(',', '.')
            ->toString();

        if (!is_numeric($normalized_price)) {
            return null;
        }

        return (float)$normalized_price;
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, int>
     */
    private function normalizeImageSizeSettings(array $settings): array
    {
        return [
            'width'      => max((int)Arr::get($settings, 'width', 1), 1),
            'height'     => max((int)Arr::get($settings, 'height', 1), 1),
            'max_width'  => max((int)Arr::get($settings, 'max_width', 1), 1),
            'max_height' => max((int)Arr::get($settings, 'max_height', 1), 1),
        ];
    }
}
