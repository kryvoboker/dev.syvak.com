<?php

declare(strict_types=1);

namespace Modules\Carousel\Services\Filament;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Carousel\Support\CarouselConfig;

class ModuleSettingsNormalizerService
{
    public function __construct(
        private readonly CarouselConfig $carousel_config,
    ) {
    }

    /**
     * @param  array<string, mixed>  $settings
     *
     * @throws ValidationException
     *
     * @return array<string, mixed>
     */
    public function normalize(array $settings): array
    {
        $active_languages = (new Language())->getActiveLanguages();
        $allowed_page_types = collect((array) config('page-settings.page_type', []))->values()->all();
        $shared_settings = Arr::get($settings, 'shared', []);
        $slides = Arr::get($settings, 'slides', []);

        Log::channel('daily')->info('Normalizing carousel module settings.', [
            'slides_count' => is_countable($slides) ? count($slides) : 0,
            'languages_count' => $active_languages->count(),
        ]);

        if (! is_array($slides) || $slides === []) {
            throw ValidationException::withMessages([
                'settings.slides' => __('carousel::admin/modules/carousel.validation.at_least_one_slide_is_required'),
            ]);
        }

        $normalized_shared_settings = [
            'page_types' => $this->normalizePageTypes(
                Arr::get($shared_settings, 'page_types', $this->carousel_config->get('storefront.default_page_types', [])),
                $allowed_page_types,
            ),
            'open_links_in_new_tab' => (bool) Arr::get($shared_settings, 'open_links_in_new_tab', true),
            'desktop_image' => $this->normalizeImageSizeBlock(
                Arr::get($shared_settings, 'desktop_image', []),
                $this->carousel_config->get('storefront.desktop_image', []),
                'desktop',
            ),
            'mobile_image' => $this->normalizeImageSizeBlock(
                Arr::get($shared_settings, 'mobile_image', []),
                $this->carousel_config->get('storefront.mobile_image', []),
                'mobile',
            ),
        ];

        $normalized_slides = collect((array) $slides)
            ->values()
            ->map(fn (mixed $slide, int $index): array => $this->normalizeSlide($slide, $index, $active_languages))
            ->sortBy('sort_order')
            ->values()
            ->all();

        return [
            'shared' => $normalized_shared_settings,
            'slides' => $normalized_slides,
        ];
    }

    /**
     * @param  Collection<int, Language>  $active_languages
     * @return array<string, mixed>
     */
    private function normalizeSlide(mixed $slide, int $index, Collection $active_languages): array
    {
        if (! is_array($slide)) {
            throw ValidationException::withMessages([
                "settings.slides.{$index}" => __('carousel::admin/modules/carousel.validation.slide_payload_must_be_an_array'),
            ]);
        }

        $translations = Arr::get($slide, 'translations', []);

        if (! is_array($translations)) {
            throw ValidationException::withMessages([
                "settings.slides.{$index}.translations" => __('carousel::admin/modules/carousel.validation.slide_translations_must_be_an_array'),
            ]);
        }

        $normalized_translations = $active_languages
            ->toBase()
            ->mapWithKeys(function (Language $language) use ($translations, $index): array {
                $language_code = (string) $language->code;
                $translation = Arr::get($translations, $language_code, []);

                if (! is_array($translation)) {
                    throw ValidationException::withMessages([
                        "settings.slides.{$index}.translations.{$language_code}" => __('carousel::admin/modules/carousel.validation.translation_payload_must_be_an_array'),
                    ]);
                }

                $normalized_translation = [
                    'language_code' => $language_code,
                    'title' => Str::squish((string) Arr::get($translation, 'title')),
                    'description' => Str::squish((string) Arr::get($translation, 'description')),
                    'button_text' => Str::squish((string) Arr::get($translation, 'button_text')),
                    'button_url' => Str::trim((string) Arr::get($translation, 'button_url')),
                    'image_url' => Str::trim((string) Arr::get($translation, 'image_url')),
                    'desktop_image' => $this->normalizeImagePath(
                        Arr::get($translation, 'desktop_image'),
                        "settings.slides.{$index}.translations.{$language_code}.desktop_image",
                    ),
                    'mobile_image' => $this->normalizeImagePath(
                        Arr::get($translation, 'mobile_image'),
                        "settings.slides.{$index}.translations.{$language_code}.mobile_image",
                    ),
                ];

                $validator = Validator::make($normalized_translation, [
                    'title' => ['nullable', 'string', 'max:255'],
                    'description' => ['nullable', 'string'],
                    'button_text' => ['nullable', 'string', 'max:255'],
                    'button_url' => ['nullable', 'url'],
                    'image_url' => ['nullable', 'url'],
                    'desktop_image' => ['nullable', 'string'],
                    'mobile_image' => ['nullable', 'string'],
                ]);

                if ($validator->fails()) {
                    throw ValidationException::withMessages(
                        collect($validator->errors()->messages())
                            ->mapWithKeys(fn (array $messages, string $key): array => [
                                "settings.slides.{$index}.translations.{$language_code}.{$key}" => $messages,
                            ])
                            ->all(),
                    );
                }

                return [$language_code => $normalized_translation];
            })
            ->all();

        Log::channel('daily')->info('Carousel slide translation payload normalized.', [
            'slide_index' => $index,
            'translations_count' => count($normalized_translations),
            'translations_with_desktop_image_count' => collect($normalized_translations)
                ->filter(fn (array $translation): bool => filled($translation['desktop_image'] ?? null))
                ->count(),
            'translations_with_mobile_image_count' => collect($normalized_translations)
                ->filter(fn (array $translation): bool => filled($translation['mobile_image'] ?? null))
                ->count(),
            'translations_without_any_slide_image_count' => collect($normalized_translations)
                ->filter(fn (array $translation): bool => blank($translation['desktop_image'] ?? null) && blank($translation['mobile_image'] ?? null))
                ->count(),
        ]);

        return [
            'is_active' => (bool) Arr::get($slide, 'is_active', true),
            'sort_order' => max((int) Arr::get($slide, 'sort_order', $index + 1), 1),
            'translations' => $normalized_translations,
        ];
    }

    /**
     * @throws ValidationException
     */
    private function normalizeImagePath(mixed $image_path, string $field): ?string
    {
        $image_path = $this->resolveTemporaryImagePath($image_path, $field);
        $image_path = Str::trim((string) $image_path);

        if (blank($image_path)) {
            return null;
        }

        if (Storage::fileExists($image_path) === false) {
            throw ValidationException::withMessages([
                $field => __('carousel::admin/modules/carousel.validation.uploaded_image_was_not_found_in_storage'),
            ]);
        }

        $mime_type = (string) Storage::mimeType($image_path);
        $allowed_mime_types = $this->carousel_config->get('uploads.accepted_mime_types', []);

        if (! in_array($mime_type, $allowed_mime_types, true)) {
            Log::channel('stack')->warning('Carousel image mime type is not allowed.', [
                'field' => $field,
                'image_path' => $image_path,
                'mime_type' => $mime_type,
            ]);

            throw ValidationException::withMessages([
                $field => __('carousel::admin/modules/carousel.validation.only_jpg_and_png_images_are_allowed'),
            ]);
        }

        return $image_path;
    }

    /**
     * Resolves Livewire temporary markers to persisted storage paths.
     *
     * Filament file uploads can send `livewire-file:*` during form hydration.
     * We normalize that value here to make sure saved settings always contain a
     * real storage path for every language and image field.
     *
     * @throws ValidationException
     */
    private function resolveTemporaryImagePath(mixed $image_path, string $field): mixed
    {
        if ($image_path instanceof TemporaryUploadedFile) {
            return $this->storeTemporaryImage($image_path, $field);
        }

        if (! is_string($image_path) || ! TemporaryUploadedFile::canUnserialize($image_path)) {
            return $image_path;
        }

        $resolved_image = TemporaryUploadedFile::unserializeFromLivewireRequest($image_path);

        if ($resolved_image instanceof TemporaryUploadedFile) {
            return $this->storeTemporaryImage($resolved_image, $field);
        }

        if (is_array($resolved_image)) {
            $first_image = collect($resolved_image)->first(fn (mixed $item): bool => $item instanceof TemporaryUploadedFile);

            if ($first_image instanceof TemporaryUploadedFile) {
                return $this->storeTemporaryImage($first_image, $field);
            }
        }

        return $image_path;
    }

    /**
     * @throws ValidationException
     */
    private function storeTemporaryImage(TemporaryUploadedFile $temporary_image, string $field): string
    {
        $stored_path = $temporary_image->storeAs(
            (string) $this->carousel_config->get('uploads.directory', 'images/modules/carousel'),
            TemporaryUploadedFile::generateHashName($temporary_image),
            ['visibility' => 'public'],
        );

        if (! is_string($stored_path) || blank($stored_path)) {
            throw ValidationException::withMessages([
                $field => __('carousel::admin/modules/carousel.validation.uploaded_image_could_not_be_persisted'),
            ]);
        }

        return $stored_path;
    }

    /**
     * @param  array<int, string>  $allowed_page_types
     * @return array<int, string>
     */
    private function normalizePageTypes(mixed $page_types, array $allowed_page_types): array
    {
        $page_types = collect(is_array($page_types) ? $page_types : [$page_types])
            ->filter(fn (mixed $page_type): bool => is_string($page_type) && filled($page_type))
            ->map(fn (string $page_type): string => Str::trim($page_type))
            ->unique()
            ->values();

        if ($page_types->isEmpty()) {
            throw ValidationException::withMessages([
                'settings.shared.page_types' => __('carousel::admin/modules/carousel.validation.select_at_least_one_page_type'),
            ]);
        }

        $invalid_page_types = $page_types->diff($allowed_page_types)->values();

        if ($invalid_page_types->isNotEmpty()) {
            throw ValidationException::withMessages([
                'settings.shared.page_types' => __('carousel::admin/modules/carousel.validation.the_selected_page_types_are_invalid'),
            ]);
        }

        return $page_types->all();
    }

    /**
     * @return array<string, int>
     */
    private function normalizeImageSizeBlock(mixed $image_size_block, mixed $defaults, string $device_type): array
    {
        $image_size_block = is_array($image_size_block) ? $image_size_block : [];
        $defaults = is_array($defaults) ? $defaults : [];

        $normalized_block = [
            'width' => max((int) Arr::get($image_size_block, 'width', Arr::get($defaults, 'width', 1)), 1),
            'height' => max((int) Arr::get($image_size_block, 'height', Arr::get($defaults, 'height', 1)), 1),
            'max_width' => max((int) Arr::get($image_size_block, 'max_width', Arr::get($defaults, 'max_width', 1)), 1),
            'max_height' => max((int) Arr::get($image_size_block, 'max_height', Arr::get($defaults, 'max_height', 1)), 1),
        ];

        $validator = Validator::make($normalized_block, [
            'width' => ['required', 'integer', 'min:1'],
            'height' => ['required', 'integer', 'min:1'],
            'max_width' => ['required', 'integer', 'min:1'],
            'max_height' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(
                collect($validator->errors()->messages())
                    ->mapWithKeys(fn (array $messages, string $key): array => [
                        "settings.shared.{$device_type}_image.{$key}" => $messages,
                    ])
                    ->all(),
            );
        }

        return $normalized_block;
    }
}
