<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Data\AppSettingsData;
use App\Services\FooterService;
use App\Supports\Services\AppSettingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppSettingsServiceContactsTest extends TestCase
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
        app()->setLocale('en');

        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('user_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->json('titles')->nullable();
            $table->json('meta_titles')->nullable();
            $table->json('meta_descriptions')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->json('socials')->nullable();
            $table->string('timezone')->nullable();
            $table->json('image_sizes')->nullable();
            $table->json('system_settings')->nullable();
            $table->json('user_settings')->nullable();
            $table->json('ai_settings')->nullable();
            $table->timestamps();
        });

        Schema::create('global_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('page_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('page_type', 100)->unique();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('user_groups')->insert([
            ['id' => 1, 'name' => 'Default', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('app_settings')->insert([
            'socials' => json_encode([]),
            'timezone' => 'UTC',
            'image_sizes' => json_encode([]),
            'system_settings' => json_encode([]),
            'user_settings' => json_encode([]),
            'ai_settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('page_settings')->insert([
            'page_type' => 'contacts',
            'settings' => json_encode([
                'emails' => [
                    ['value' => 'info@example.com', 'sort_order' => 10],
                ],
                'phones' => [
                    ['type' => 'mobile', 'value' => '+380 00 000 00 00', 'sort_order' => 10],
                ],
                'localized' => [
                    '1' => [
                        'title' => 'Contacts',
                        'working_hours' => ['content' => 'Monday-Friday'],
                    ],
                ],
                'map' => [
                    'latitude' => 50.4501,
                    'longitude' => 30.5234,
                    'iframe' => '<iframe></iframe>',
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_app_settings_service_exposes_contacts_page_data(): void
    {
        $service = app(AppSettingsService::class);
        $service->setSettings();
        $settings = $service->getSettings();

        $this->assertInstanceOf(AppSettingsData::class, $settings);
        $this->assertSame('info@example.com', data_get($settings->contact_emails?->first(), 'value'));
        $this->assertSame('+380 00 000 00 00', data_get($settings->contact_phones?->first(), 'value'));
        $this->assertSame('Monday-Friday', data_get($settings->work_time, 'en'));
        $this->assertSame('50.4501,30.5234', $settings->coordinates);
        $this->assertSame('<iframe></iframe>', $settings->iframe_map);
    }

    public function test_footer_service_reads_ordered_phone_rows_and_uses_fallback(): void
    {
        $footer_service = new FooterService();
        $parse_phones = (new \ReflectionClass($footer_service))->getMethod('parsePhones');
        $parse_phones->setAccessible(true);

        $this->assertSame(
            ['first phone', 'second phone'],
            $parse_phones->invoke($footer_service, new Collection([
                ['value' => 'first phone', 'sort_order' => 10],
                ['value' => 'second phone', 'sort_order' => 20],
            ])),
        );
        $this->assertSame(['0 800 000 000'], $parse_phones->invoke($footer_service, []));
    }
}
