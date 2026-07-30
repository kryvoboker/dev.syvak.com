<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Data\AppSettingsData;
use App\Models\Catalogs\Categories\Category;
use App\Services\HeaderService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use App\Supports\Services\AppSettingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use Tests\TestCase;

class HeaderServiceTest extends TestCase
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

        $this->createTables();

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        app()->setLocale('en');
        $this->bindAppSettings();
        app()->instance(PageSettingsBootstrapService::class, new class () extends PageSettingsBootstrapService {
            public function getCategorySettings(): array
            {
                return [
                    'header' => [
                        'categories' => [2, 1, 999],
                    ],
                ];
            }
        });
    }

    public function test_header_service_returns_configured_categories_in_saved_order(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'parent_id' => null, 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'parent_id' => null, 'sort_order' => 3, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('category_descriptions')->insert([
            ['category_id' => 1, 'language_id' => 1, 'name' => 'Hoodies', 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => 2, 'language_id' => 1, 'name' => 'Gifts', 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => 3, 'language_id' => 1, 'name' => 'Inactive', 'created_at' => now(), 'updated_at' => now()],
        ]);

        foreach ([1 => 'hoodies', 2 => 'gifts', 3 => 'inactive'] as $category_id => $slug) {
            DB::table('slugs')->insert([
                'sluggable_id' => $category_id,
                'sluggable_type' => Category::class,
                'language_id' => 1,
                'slug' => $slug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $header_data = app(HeaderService::class)();

        $this->assertSame([2, 1], collect($header_data['header_categories'])->pluck('id')->all());
        $this->assertSame(['Gifts', 'Hoodies'], collect($header_data['header_categories'])->pluck('descriptions.name')->all());
        $this->assertSame(['gifts', 'hoodies'], collect($header_data['header_categories'])->pluck('slug')->all());
        $this->assertCount(2, $header_data['categories']);
    }

    private function bindAppSettings(): void
    {
        $app_settings_service = new AppSettingsService();
        $property = new ReflectionProperty(AppSettingsService::class, 'app_settings_data');
        $property->setAccessible(true);
        $property->setValue($app_settings_service, AppSettingsData::fromArray([
            'image_sizes' => [['name' => 'logo', 'width' => 100, 'height' => 100]],
            'system_settings' => ['images' => ['path_to_logo' => 'images/logo.png']],
            'socials' => ['en' => []],
            'language_id' => 1,
        ]));

        app()->instance(AppSettingsService::class, $app_settings_service);
    }

    private function createTables(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('category_descriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('h1_title')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();
        });

        Schema::create('slugs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sluggable_id');
            $table->string('sluggable_type');
            $table->unsignedBigInteger('language_id');
            $table->string('slug');
            $table->timestamps();
        });
    }
}
