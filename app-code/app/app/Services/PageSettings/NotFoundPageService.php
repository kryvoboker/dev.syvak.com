<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Models\ApplicationSettings\Language;
use App\Services\FooterService;
use App\Services\HeaderService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final readonly class NotFoundPageService
{
    public function __construct(
        private PageSettingsBootstrapService $page_settings_bootstrap_service,
        private HeaderService $header_service,
        private FooterService $footer_service,
    ) {
    }

    /**
     *
     * @throws Throwable
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $not_found_data = $this->getNotFoundData();
        $header_data = ($this->header_service)([
            'breadcrumbs' => [],
        ]);

        return [
            'page_type' => $this->stringValue(config('page-settings.page_type.not_found', 'not_found')),
            'page_title' => $not_found_data['title'],
            'not_found_data' => $not_found_data,
            'header_data' => $header_data,
            'footer_data' => ($this->footer_service)([
                'categories' => $header_data['categories'],
            ]),
        ];
    }

    /**
     *
     * @throws Throwable
     * @return array<string, mixed>
     */
    public function getNotFoundData(): array
    {
        $settings = $this->page_settings_bootstrap_service->getNotFoundSettings();
        $localized_settings = $this->resolveLocalizedSettings($settings);

        $description = $this->resolveDescription($localized_settings);

        return [
            'title' => $this->resolveTitle($localized_settings),
            ...($description !== null ? ['description' => $description] : []),
            'link' => [
                'label' => $this->resolveLinkLabel($localized_settings),
                'url' => $this->resolveLinkUrl($localized_settings),
            ],
            'images' => $this->resolveImages($settings),
        ];
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    private function resolveLocalizedSettings(array $settings): array
    {
        $localized_settings = Arr::get($settings, 'localized', []);

        if (!is_array($localized_settings)) {
            return [];
        }

        $app_settings = get_app_settings();
        $language_id = Language::query()
            ->where('code', app()->getLocale())
            ->where('is_active', true)
            ->value('id');

        if ($language_id === null && $app_settings !== null) {
            $language_id = $app_settings->language_id;
        }

        $language_id = $this->stringValue($language_id);

        $localized_content = Arr::get($localized_settings, $language_id, []);

        if (is_array($localized_content)) {
            return $this->stringKeyedArray($localized_content);
        }

        $first_content = Arr::first($localized_settings);

        return is_array($first_content) ? $this->stringKeyedArray($first_content) : [];
    }

    /**
     * @param array<string, mixed> $localized_settings
     */
    private function resolveTitle(array $localized_settings): string
    {
        $title = Str::trim($this->stringValue(Arr::get($localized_settings, 'title', '')));

        return filled($title) ? $title : $this->stringValue(__('http-statuses.404'));
    }

    /**
     * @param array<string, mixed> $localized_settings
     */
    private function resolveDescription(array $localized_settings): ?string
    {
        $description = Str::trim($this->stringValue(Arr::get($localized_settings, 'description', '')));

        return filled($description) ? $description : null;
    }

    /**
     * @param array<string, mixed> $localized_settings
     */
    private function resolveLinkLabel(array $localized_settings): string
    {
        $label = Str::trim($this->stringValue(Arr::get($localized_settings, 'link.label', '')));

        return filled($label)
            ? $label
            : $this->stringValue(__('storefront/pages/not-found.buttons.go_home'));
    }

    /**
     * @param array<string, mixed> $localized_settings
     */
    private function resolveLinkUrl(array $localized_settings): string
    {
        $url = Str::trim($this->stringValue(Arr::get($localized_settings, 'link.url', '')));

        if (
            Str::startsWith($url, ['http://', 'https://']) ||
            (Str::startsWith($url, '/') && !Str::startsWith($url, '//'))
        ) {
            return $url;
        }

        return localized_route('catalog.home');
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<int, array{urls:array<string,string>,width:int,height:int,is_square:bool,custom_css_classes:string,sort_order:int}>
     */
    private function resolveImages(array $settings): array
    {
        $images = Arr::get($settings, 'images', []);

        if (!is_array($images)) {
            return [];
        }

        $resolved_images = collect($images)
            ->filter(fn (mixed $image): bool => is_array($image) && filled(Arr::get($image, 'path')))
            ->sortBy(fn (array $image): int => $this->integerValue(Arr::get($image, 'sort_order', 0)))
            ->values()
            ->map(function (array $image): ?array {
                $path = Str::ltrim($this->stringValue(Arr::get($image, 'path')), '/');

                if (blank($path) || Storage::fileExists($path) === false) {
                    return null;
                }

                try {
                    $width = max(1, $this->integerValue(Arr::get($image, 'width', 600)));
                    $height = max(1, $this->integerValue(Arr::get($image, 'height', $width)));

                    return [
                        'urls' => multiple_convert_img_and_get_url(
                            $path,
                            $width,
                            $height,
                            (bool)Arr::get($image, 'is_square', true),
                            $this->stringValue(Arr::get($image, 'background', 'transparent')),
                        ),
                        'width' => $width,
                        'height' => $height,
                        'is_square' => (bool)Arr::get($image, 'is_square', true),
                        'custom_css_classes' => Str::squish($this->stringValue(Arr::get($image, 'custom_css_classes', ''))),
                        'sort_order' => $this->integerValue(Arr::get($image, 'sort_order', 0)),
                    ];
                } catch (Throwable $throwable) {
                    Log::channel('stack')->warning('Public 404 image resolution failed.', [
                        'image_path' => $path,
                        'exception' => $throwable,
                    ]);

                    return null;
                }
            })
            ->filter(fn (?array $image): bool => $image !== null)
            ->all();

        /** @var array<int, array{urls: array<string, string>, width: int, height: int, is_square: bool, custom_css_classes: string, sort_order: int}> $resolved_images */
        return $resolved_images;
    }

    /** @return array<string, mixed> */
    private function stringKeyedArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
