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
            ->filter(fn (ModuleDefinition $definition): bool => $definition->nwidart_name === 'Carousel')
            ->map(fn (ModuleDefinition $definition): array => $this->mapDefinitionInstances($definition, $page_type))
            ->collapse()
            ->values();

        if ($carousel_modules->isEmpty()) {
            Log::channel('stack')->warning('No active carousel modules were resolved for placement.', [
                'placement' => $placement,
                'page_type' => $page_type,
            ]);
        }

        Log::channel('daily')->info('Carousel modules resolved for storefront context.', [
            'placement'              => $placement,
            'page_type'              => $page_type,
            'resolved_modules_count' => $carousel_modules->count(),
        ]);

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
            ->filter(fn (ModuleInstance $instance): bool => $this->matchesPageType($instance, $page_type))
            ->map(function (ModuleInstance $instance): array {
                $instance_settings = is_array($instance->settings) ? $instance->settings : [];
                $shared_settings   = Arr::get($instance_settings, 'shared', []);
                $page_types        = collect(Arr::get($shared_settings, 'page_types', []))
                    ->filter(fn (mixed $item): bool => is_string($item) && filled($item))
                    ->values()
                    ->all();
                $slides = collect(Arr::get($instance_settings, 'slides', []))
                    ->filter(fn (mixed $slide): bool => is_array($slide) && Arr::get($slide, 'is_active', true))
                    ->sortBy(fn (array $slide): int => (int) Arr::get($slide, 'sort_order', 0))
                    ->values()
                    ->map(fn (array $slide): array => $this->mapSlide($slide, $shared_settings))
                    ->filter(fn (array $slide): bool => $slide !== [])
                    ->values()
                    ->all();

                if ($slides === []) {
                    Log::channel('stack')->warning('Carousel instance resolved without active slides.', [
                        'instance_id' => $instance->id,
                    ]);
                }

                Log::channel('daily')->info('Carousel instance payload prepared.', [
                    'instance_id'         => $instance->id,
                    'page_types_count'    => count($page_types),
                    'loaded_slides_count' => count($slides),
                ]);

                return [
                    'instance_id'           => $instance->id,
                    'name'                  => $instance->name,
                    'placement'             => $instance->placement,
                    'page_types'            => $page_types,
                    'open_links_in_new_tab' => (bool) Arr::get($shared_settings, 'open_links_in_new_tab', true),
                    'slides'                => $slides,
                ];
            })
            ->filter(fn (array $module_data): bool => $module_data['slides'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $slide
     * @param  array<string, mixed>  $shared_settings
     * @return array<string, mixed>
     */
    private function mapSlide(array $slide, array $shared_settings): array
    {
        $translations   = Arr::get($slide, 'translations', []);
        $current_locale = app()->getLocale();
        $translation    = Arr::get($translations, $current_locale);

        if (! is_array($translation)) {
            $translation = collect($translations)->first(fn (mixed $item): bool => is_array($item));
        }

        if (! is_array($translation)) {
            Log::channel('stack')->warning('Carousel slide skipped because translation payload is invalid.', [
                'locale' => $current_locale,
            ]);

            return [];
        }

        $desktop_image_settings = $this->normalizeImageSizeSettings(Arr::get($shared_settings, 'desktop_image', []));
        $mobile_image_settings  = $this->normalizeImageSizeSettings(Arr::get($shared_settings, 'mobile_image', []));

        $desktop_width  = min($desktop_image_settings['width'], $desktop_image_settings['max_width']);
        $desktop_height = min($desktop_image_settings['height'], $desktop_image_settings['max_height']);
        $mobile_width   = min($mobile_image_settings['width'], $mobile_image_settings['max_width']);
        $mobile_height  = min($mobile_image_settings['height'], $mobile_image_settings['max_height']);
        $price          = $this->normalizePrice(Arr::get($translation, 'price'));

        return [
            'title'           => (string) Arr::get($translation, 'title', ''),
            'description'     => (string) Arr::get($translation, 'description', ''),
            'button_text'     => (string) Arr::get($translation, 'button_text', ''),
            'button_url'      => (string) Arr::get($translation, 'button_url', ''),
            'image_url'       => (string) Arr::get($translation, 'image_url', ''),
            'price'           => $price,
            'formatted_price' => $price !== null ? format_price($price) : null,
            'sort_order'      => (int) Arr::get($slide, 'sort_order', 0),
            'desktop_image'   => [
                'urls' => multiple_convert_img_and_get_url(
                    (string) Arr::get($translation, 'desktop_image'),
                    $desktop_width,
                    $desktop_height,
                    is_square: false,
                ),
                'width'  => $desktop_width,
                'height' => $desktop_height,
            ],
            'mobile_image' => [
                'urls' => multiple_convert_img_and_get_url(
                    (string) Arr::get($translation, 'mobile_image'),
                    $mobile_width,
                    $mobile_height,
                    is_square: false,
                ),
                'width'  => $mobile_width,
                'height' => $mobile_height,
            ],
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
            return (float) $price;
        }

        if (! is_string($price)) {
            return null;
        }

        $normalized_price = Str::of($price)
            ->trim()
            ->replace(',', '.')
            ->toString();

        if (! is_numeric($normalized_price)) {
            return null;
        }

        return (float) $normalized_price;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, int>
     */
    private function normalizeImageSizeSettings(array $settings): array
    {
        return [
            'width'      => max((int) Arr::get($settings, 'width', 1), 1),
            'height'     => max((int) Arr::get($settings, 'height', 1), 1),
            'max_width'  => max((int) Arr::get($settings, 'max_width', 1), 1),
            'max_height' => max((int) Arr::get($settings, 'max_height', 1), 1),
        ];
    }
}
