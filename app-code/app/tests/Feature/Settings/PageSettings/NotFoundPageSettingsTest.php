<?php

declare(strict_types=1);

namespace Tests\Feature\Settings\PageSettings;

use App\Filament\Resources\PageSettings\NotFound\NotFoundResource;
use App\Filament\Resources\PageSettings\NotFound\Pages\EditNotFound;
use App\Models\PageSettings\PageSetting;
use App\Models\Slug;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotFoundPageSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('page_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('page_type', 100)->unique();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('slugs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sluggable_id');
            $table->string('sluggable_type');
            $table->unsignedBigInteger('language_id');
            $table->string('slug', 500);
            $table->timestamps();
            $table->unique(['sluggable_type', 'sluggable_id', 'language_id']);
            $table->unique(['language_id', 'slug']);
        });

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_bootstrap_creates_a_normalized_not_found_singleton(): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapNotFoundPageSetting();

        $this->assertSame(PageSetting::PAGE_TYPE_NOT_FOUND, $page_setting->page_type);
        $this->assertSame(1, (int) data_get($page_setting->settings, 'meta.contract_version'));
        $this->assertArrayHasKey('1', (array) data_get($page_setting->settings, 'localized'));
        $this->assertArrayHasKey('2', (array) data_get($page_setting->settings, 'localized'));
        $this->assertSame('', data_get($page_setting->settings, 'images.slot_1.path'));
        $this->assertSame(10, (int) data_get($page_setting->settings, 'images.slot_1.sort_order'));

        $second_page_setting = app(PageSettingsBootstrapService::class)->bootstrapNotFoundPageSetting();

        $this->assertSame($page_setting->id, $second_page_setting->id);
        $this->assertSame(1, PageSetting::query()->where('page_type', PageSetting::PAGE_TYPE_NOT_FOUND)->count());
    }

    public function test_not_found_settings_normalize_image_contract_without_storing_slugs_in_json(): void
    {
        $settings = app(PageSettingsBootstrapService::class)->normalizeNotFoundSettings([
            'localized' => [
                '1' => [
                    'title' => '  Missing page  ',
                    'link' => [
                        'url' => ' /en ',
                    ],
                ],
            ],
            'images' => [
                'slot_1' => [
                    'path' => 'images/not-found/one.png',
                    'width' => 720,
                    'height' => 480,
                    'is_square' => false,
                    'background' => '#ffffff',
                    'custom_css_classes' => '  md:w-1/2   opacity-75 ',
                    'sort_order' => 2,
                ],
                'slot_2' => [
                    'background' => 'invalid-color',
                ],
            ],
        ]);

        $this->assertSame('Missing page', data_get($settings, 'localized.1.title'));
        $this->assertSame('/en', data_get($settings, 'localized.1.link.url'));
        $this->assertSame(720, (int) data_get($settings, 'images.slot_1.width'));
        $this->assertFalse((bool) data_get($settings, 'images.slot_1.is_square'));
        $this->assertSame('#ffffff', data_get($settings, 'images.slot_1.background'));
        $this->assertSame('md:w-1/2 opacity-75', data_get($settings, 'images.slot_1.custom_css_classes'));
        $this->assertSame('transparent', data_get($settings, 'images.slot_2.background'));
        $this->assertArrayNotHasKey('slugs', $settings);
        $this->assertArrayNotHasKey('seo', (array) Arr::get($settings, 'localized.1', []));
    }

    public function test_not_found_page_setting_uses_the_shared_polymorphic_slug_relation(): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapNotFoundPageSetting();

        $page_setting->slugs()->create([
            'language_id' => 1,
            'slug' => 'page-not-found',
        ]);

        $slug = Slug::query()->firstOrFail();

        $this->assertSame(PageSetting::class, $slug->sluggable_type);
        $this->assertSame($page_setting->id, (int) $slug->sluggable_id);
        $this->assertSame('page-not-found', $page_setting->getSlugByLanguageId(1));

        /** @var PageSetting $sluggable */
        $sluggable = $slug->sluggable;

        $this->assertSame(PageSetting::PAGE_TYPE_NOT_FOUND, $sluggable->page_type);
    }

    public function test_not_found_resource_is_a_singleton_edit_page(): void
    {
        $pages = NotFoundResource::getPages();

        $this->assertSame(['index'], array_keys($pages));
        $this->assertSame(EditNotFound::class, $pages['index']->getPage());
        $this->assertFalse(NotFoundResource::canCreate());
        $this->assertFalse(NotFoundResource::canDeleteAny());
    }
}
