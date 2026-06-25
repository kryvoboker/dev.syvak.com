<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\ApplicationSettings\Language;
use App\Models\Modules\ModuleDefinition;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Carousel\Services\CarouselModuleDataService;
use Modules\Carousel\Services\ModuleSettingsNormalizerService;
use Tests\TestCase;

class CarouselModuleServicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('cache.default', 'array');

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Storage::fake(config('filesystems.default', 'public'));
        Storage::fake('tmp-for-tests');

        $this->createLanguagesTable();
        $this->createModuleDefinitionsTable();
        $this->createModuleInstancesTable();
        $this->seedLanguages();
        $this->seedCarouselImages();
    }

    public function test_carousel_settings_normalizer_returns_localized_sorted_payload(): void
    {
        $settings = [
            'shared' => [
                'page_types'            => ['home'],
                'open_links_in_new_tab' => true,
                'desktop_image'         => [
                    'width'      => 1220,
                    'height'     => 720,
                    'max_width'  => 1920,
                    'max_height' => 1080,
                ],
                'mobile_image' => [
                    'width'      => 360,
                    'height'     => 640,
                    'max_width'  => 768,
                    'max_height' => 1280,
                ],
            ],
            'slides' => [
                [
                    'is_active'    => true,
                    'sort_order'   => 2,
                    'translations' => $this->makeTranslations('Second slide'),
                ],
                [
                    'is_active'    => true,
                    'sort_order'   => 1,
                    'translations' => $this->makeTranslations('First slide'),
                ],
            ],
        ];

        $normalized_settings = $this->app->make(ModuleSettingsNormalizerService::class)->normalize($settings);

        $this->assertSame(['home'], $normalized_settings['shared']['page_types']);
        $this->assertCount(2, $normalized_settings['slides']);
        $this->assertSame(1, $normalized_settings['slides'][0]['sort_order']);
        $this->assertSame('First slide uk', $normalized_settings['slides'][0]['translations']['uk']['title']);
        $this->assertSame('First slide en', $normalized_settings['slides'][0]['translations']['en']['title']);
    }

    public function test_carousel_module_data_service_returns_only_instances_for_requested_page_type(): void
    {
        $definition = ModuleDefinition::query()->create([
            'name'                     => 'Carousel',
            'slug'                     => 'carousel',
            'nwidart_name'             => 'Carousel',
            'module_path'              => '/var/modules/Carousel',
            'description'              => 'Carousel',
            'is_installed'             => true,
            'is_enabled'               => true,
            'is_enabled_in_filesystem' => true,
            'sort_order'               => 1,
            'settings_schema'          => [],
            'meta'                     => [],
        ]);

        $definition->instances()->create([
            'name'        => 'Homepage Carousel',
            'placement'   => 'hero',
            'context_key' => null,
            'is_enabled'  => true,
            'sort_order'  => 1,
            'settings'    => $this->makeCarouselSettings(['home']),
            'meta'        => [],
        ]);

        $definition->instances()->create([
            'name'        => 'Category Carousel',
            'placement'   => 'hero',
            'context_key' => null,
            'is_enabled'  => true,
            'sort_order'  => 2,
            'settings'    => $this->makeCarouselSettings(['category']),
            'meta'        => [],
        ]);

        $resolved_modules = $this->app->make(CarouselModuleDataService::class)->resolveForPlacement('hero', 'home');

        $this->assertCount(1, $resolved_modules);
        $this->assertSame('Homepage Carousel', $resolved_modules[0]['name']);
        $this->assertCount(1, $resolved_modules[0]['slides']);
        $this->assertSame('Main slide ' . app()->getLocale(), $resolved_modules[0]['slides'][0]['title']);
        $this->assertArrayHasKey('original_thumb', $resolved_modules[0]['slides'][0]['desktop_image']['urls']);
    }

    public function test_carousel_settings_normalizer_accepts_empty_optional_text_and_links(): void
    {
        $settings = [
            'shared' => [
                'page_types'    => ['home'],
                'desktop_image' => [
                    'width'      => 1220,
                    'height'     => 720,
                    'max_width'  => 1920,
                    'max_height' => 1080,
                ],
                'mobile_image' => [
                    'width'      => 360,
                    'height'     => 640,
                    'max_width'  => 768,
                    'max_height' => 1280,
                ],
            ],
            'slides' => [
                [
                    'is_active'    => true,
                    'sort_order'   => 1,
                    'translations' => $this->makeOptionalTranslations(),
                ],
            ],
        ];

        $normalized_settings = $this->app->make(ModuleSettingsNormalizerService::class)->normalize($settings);

        $this->assertSame('', $normalized_settings['slides'][0]['translations']['uk']['title']);
        $this->assertSame('', $normalized_settings['slides'][0]['translations']['uk']['description']);
        $this->assertSame('', $normalized_settings['slides'][0]['translations']['uk']['button_text']);
        $this->assertSame('', $normalized_settings['slides'][0]['translations']['uk']['button_url']);
        $this->assertSame('', $normalized_settings['slides'][0]['translations']['uk']['image_url']);
    }

    public function test_carousel_settings_normalizer_accepts_missing_images_for_all_languages(): void
    {
        $settings = [
            'shared' => [
                'page_types'    => ['home'],
                'desktop_image' => [
                    'width'      => 1220,
                    'height'     => 720,
                    'max_width'  => 1920,
                    'max_height' => 1080,
                ],
                'mobile_image' => [
                    'width'      => 360,
                    'height'     => 640,
                    'max_width'  => 768,
                    'max_height' => 1280,
                ],
            ],
            'slides' => [
                [
                    'is_active'    => true,
                    'sort_order'   => 1,
                    'translations' => $this->makeTextOnlyTranslations(),
                ],
            ],
        ];

        $normalized_settings = $this->app->make(ModuleSettingsNormalizerService::class)->normalize($settings);

        $this->assertNull($normalized_settings['slides'][0]['translations']['uk']['desktop_image']);
        $this->assertNull($normalized_settings['slides'][0]['translations']['uk']['mobile_image']);
        $this->assertNull($normalized_settings['slides'][0]['translations']['en']['desktop_image']);
        $this->assertNull($normalized_settings['slides'][0]['translations']['en']['mobile_image']);
    }

    public function test_carousel_settings_normalizer_resolves_livewire_temp_image_markers_for_all_languages(): void
    {
        $this->putLivewireTemporaryImage('uk-desktop-temp.jpg');
        $this->putLivewireTemporaryImage('uk-mobile-temp.jpg');
        $this->putLivewireTemporaryImage('en-desktop-temp.jpg');
        $this->putLivewireTemporaryImage('en-mobile-temp.jpg');

        $settings = [
            'shared' => [
                'page_types'    => ['home'],
                'desktop_image' => [
                    'width'      => 1220,
                    'height'     => 720,
                    'max_width'  => 1920,
                    'max_height' => 1080,
                ],
                'mobile_image' => [
                    'width'      => 360,
                    'height'     => 640,
                    'max_width'  => 768,
                    'max_height' => 1280,
                ],
            ],
            'slides' => [
                [
                    'is_active'    => true,
                    'sort_order'   => 1,
                    'translations' => [
                        'uk' => [
                            'language_code' => 'uk',
                            'title'         => 'Slide uk',
                            'description'   => '',
                            'button_text'   => '',
                            'button_url'    => '',
                            'image_url'     => '',
                            'desktop_image' => 'livewire-file:uk-desktop-temp.jpg',
                            'mobile_image'  => 'livewire-file:uk-mobile-temp.jpg',
                        ],
                        'en' => [
                            'language_code' => 'en',
                            'title'         => 'Slide en',
                            'description'   => '',
                            'button_text'   => '',
                            'button_url'    => '',
                            'image_url'     => '',
                            'desktop_image' => 'livewire-file:en-desktop-temp.jpg',
                            'mobile_image'  => 'livewire-file:en-mobile-temp.jpg',
                        ],
                    ],
                ],
            ],
        ];

        $normalized_settings = $this->app->make(ModuleSettingsNormalizerService::class)->normalize($settings);

        foreach (['uk', 'en'] as $language_code) {
            $desktop_image = (string) $normalized_settings['slides'][0]['translations'][$language_code]['desktop_image'];
            $mobile_image  = (string) $normalized_settings['slides'][0]['translations'][$language_code]['mobile_image'];

            $this->assertStringNotContainsString('livewire-file:', $desktop_image);
            $this->assertStringNotContainsString('livewire-file:', $mobile_image);
            $this->assertTrue(Storage::fileExists($desktop_image));
            $this->assertTrue(Storage::fileExists($mobile_image));
            $this->assertSame('image/jpeg', Storage::mimeType($desktop_image));
            $this->assertSame('image/jpeg', Storage::mimeType($mobile_image));
        }
    }

    public function test_carousel_module_data_service_uses_image_fallback_locale_when_current_locale_images_are_missing(): void
    {
        app()->setLocale('uk');

        $definition = ModuleDefinition::query()->create([
            'name'                     => 'Carousel',
            'slug'                     => 'carousel-fallback',
            'nwidart_name'             => 'Carousel',
            'module_path'              => '/var/modules/Carousel',
            'description'              => 'Carousel',
            'is_installed'             => true,
            'is_enabled'               => true,
            'is_enabled_in_filesystem' => true,
            'sort_order'               => 1,
            'settings_schema'          => [],
            'meta'                     => [],
        ]);

        $settings                                                     = $this->makeCarouselSettings(['home']);
        $settings['slides'][0]['translations']['uk']['desktop_image'] = null;
        $settings['slides'][0]['translations']['uk']['mobile_image']  = null;

        $definition->instances()->create([
            'name'        => 'Homepage Carousel',
            'placement'   => 'hero',
            'context_key' => null,
            'is_enabled'  => true,
            'sort_order'  => 1,
            'settings'    => $settings,
            'meta'        => [],
        ]);

        $resolved_modules = $this->app->make(CarouselModuleDataService::class)->resolveForPlacement('hero', 'home');

        $this->assertCount(1, $resolved_modules);
        $this->assertSame('Main slide uk', $resolved_modules[0]['slides'][0]['title']);
        $this->assertNotEmpty($resolved_modules[0]['slides'][0]['desktop_image']['urls']);
        $this->assertNotEmpty($resolved_modules[0]['slides'][0]['mobile_image']['urls']);
    }

    public function test_carousel_module_data_service_keeps_text_only_slide_when_all_images_are_missing(): void
    {
        app()->setLocale('uk');

        $definition = ModuleDefinition::query()->create([
            'name'                     => 'Carousel',
            'slug'                     => 'carousel-text-only',
            'nwidart_name'             => 'Carousel',
            'module_path'              => '/var/modules/Carousel',
            'description'              => 'Carousel',
            'is_installed'             => true,
            'is_enabled'               => true,
            'is_enabled_in_filesystem' => true,
            'sort_order'               => 1,
            'settings_schema'          => [],
            'meta'                     => [],
        ]);

        $settings                              = $this->makeCarouselSettings(['home']);
        $settings['slides'][0]['translations'] = $this->makeTextOnlyTranslations();

        $definition->instances()->create([
            'name'        => 'Text only Carousel',
            'placement'   => 'hero',
            'context_key' => null,
            'is_enabled'  => true,
            'sort_order'  => 1,
            'settings'    => $settings,
            'meta'        => [],
        ]);

        $resolved_modules = $this->app->make(CarouselModuleDataService::class)->resolveForPlacement('hero', 'home');

        $this->assertCount(1, $resolved_modules);
        $this->assertCount(1, $resolved_modules[0]['slides']);
        $this->assertSame([], $resolved_modules[0]['slides'][0]['desktop_image']['urls']);
        $this->assertSame([], $resolved_modules[0]['slides'][0]['mobile_image']['urls']);
        $this->assertSame('Text only slide uk', $resolved_modules[0]['slides'][0]['title']);
    }

    private function seedLanguages(): void
    {
        Language::query()->create([
            'code'       => 'uk',
            'name'       => 'Ukrainian',
            'is_active'  => true,
            'is_default' => true,
        ]);

        Language::query()->create([
            'code'       => 'en',
            'name'       => 'English',
            'is_active'  => true,
            'is_default' => false,
        ]);
    }

    private function seedCarouselImages(): void
    {
        Storage::put('images/modules/carousel/desktop-slide.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO2Z0VcAAAAASUVORK5CYII='));
        Storage::put('images/modules/carousel/mobile-slide.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO2Z0VcAAAAASUVORK5CYII='));
    }

    private function putLivewireTemporaryImage(string $filename): void
    {
        Storage::disk('tmp-for-tests')->put('livewire-tmp/' . $filename, base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAAIAAgDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAT/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAwX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCVAAH/2Q=='));
    }

    /**
     * @param  array<int, string>  $page_types
     * @return array<string, mixed>
     */
    private function makeCarouselSettings(array $page_types): array
    {
        return [
            'shared' => [
                'page_types'            => $page_types,
                'open_links_in_new_tab' => true,
                'desktop_image'         => [
                    'width'      => 1220,
                    'height'     => 720,
                    'max_width'  => 1920,
                    'max_height' => 1080,
                ],
                'mobile_image' => [
                    'width'      => 360,
                    'height'     => 640,
                    'max_width'  => 768,
                    'max_height' => 1280,
                ],
            ],
            'slides' => [
                [
                    'is_active'    => true,
                    'sort_order'   => 1,
                    'translations' => $this->makeTranslations('Main slide'),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function makeTranslations(string $base_title): array
    {
        return [
            'uk' => [
                'language_code' => 'uk',
                'title'         => $base_title . ' uk',
                'description'   => $base_title . ' description uk',
                'button_text'   => 'Open uk',
                'button_url'    => 'https://example.com/uk',
                'image_url'     => 'https://example.com/image-uk',
                'desktop_image' => 'images/modules/carousel/desktop-slide.png',
                'mobile_image'  => 'images/modules/carousel/mobile-slide.png',
            ],
            'en' => [
                'language_code' => 'en',
                'title'         => $base_title . ' en',
                'description'   => $base_title . ' description en',
                'button_text'   => 'Open en',
                'button_url'    => 'https://example.com/en',
                'image_url'     => 'https://example.com/image-en',
                'desktop_image' => 'images/modules/carousel/desktop-slide.png',
                'mobile_image'  => 'images/modules/carousel/mobile-slide.png',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function makeOptionalTranslations(): array
    {
        return [
            'uk' => [
                'language_code' => 'uk',
                'title'         => '',
                'description'   => '',
                'button_text'   => '',
                'button_url'    => '',
                'image_url'     => '',
                'desktop_image' => 'images/modules/carousel/desktop-slide.png',
                'mobile_image'  => 'images/modules/carousel/mobile-slide.png',
            ],
            'en' => [
                'language_code' => 'en',
                'title'         => '',
                'description'   => '',
                'button_text'   => '',
                'button_url'    => '',
                'image_url'     => '',
                'desktop_image' => 'images/modules/carousel/desktop-slide.png',
                'mobile_image'  => 'images/modules/carousel/mobile-slide.png',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function makeTextOnlyTranslations(): array
    {
        return [
            'uk' => [
                'language_code' => 'uk',
                'title'         => 'Text only slide uk',
                'description'   => 'Description uk',
                'button_text'   => '',
                'button_url'    => '',
                'image_url'     => '',
                'desktop_image' => null,
                'mobile_image'  => null,
            ],
            'en' => [
                'language_code' => 'en',
                'title'         => 'Text only slide en',
                'description'   => 'Description en',
                'button_text'   => '',
                'button_url'    => '',
                'image_url'     => '',
                'desktop_image' => null,
                'mobile_image'  => null,
            ],
        ];
    }

    private function createLanguagesTable(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    private function createModuleDefinitionsTable(): void
    {
        Schema::create('module_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('nwidart_name', 255)->unique();
            $table->string('module_path', 1000)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_installed')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_enabled_in_filesystem')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings_schema')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    private function createModuleInstancesTable(): void
    {
        Schema::create('module_instances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_definition_id')->constrained('module_definitions')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('placement', 255)->nullable();
            $table->string('context_key', 255)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
}
