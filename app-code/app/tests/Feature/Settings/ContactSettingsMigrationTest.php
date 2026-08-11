<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContactSettingsMigrationTest extends TestCase
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
        });

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('contact_emails', 500)->nullable();
            $table->string('contact_phones', 500)->nullable();
            $table->string('work_time')->nullable();
            $table->json('contact_addresses')->nullable();
            $table->string('coordinates')->nullable();
            $table->text('iframe_map')->nullable();
            $table->timestamps();
        });

        Schema::create('page_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('page_type', 100)->unique();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en'],
            ['id' => 2, 'code' => 'uk'],
        ]);
    }

    public function test_legacy_contact_data_is_moved_without_overwriting_contacts_values(): void
    {
        DB::table('app_settings')->insert([
            'contact_emails' => json_encode(['en' => 'old@example.com, second@example.com']),
            'contact_phones' => json_encode(['en' => '+380 00 000 00 00']),
            'work_time' => json_encode(['en' => 'Monday-Friday']),
            'contact_addresses' => json_encode(['en' => ['Old address']]),
            'coordinates' => '50.4501,30.5234',
            'iframe_map' => '<iframe></iframe>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('page_settings')->insert([
            'page_type' => 'contacts',
            'settings' => json_encode([
                'phones' => [
                    ['type' => 'mobile', 'value' => 'existing phone', 'sort_order' => 1],
                ],
                'localized' => [
                    '1' => [
                        'working_hours' => ['content' => 'Existing working hours'],
                    ],
                ],
                'map' => ['iframe' => '<iframe existing></iframe>'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_08_06_174541_move_contact_data_to_contacts_page_settings.php');
        $migration->up();

        $settings = json_decode((string) DB::table('page_settings')->value('settings'), true);

        $this->assertSame('existing phone', data_get($settings, 'phones.0.value'));
        $this->assertSame('old@example.com', data_get($settings, 'emails.0.value'));
        $this->assertSame('second@example.com', data_get($settings, 'emails.1.value'));
        $this->assertSame('Existing working hours', data_get($settings, 'localized.1.working_hours.content'));
        $this->assertSame('Old address', data_get($settings, 'addresses.0.localized.1.value'));
        $this->assertSame(50.4501, data_get($settings, 'map.latitude'));
        $this->assertSame(30.5234, data_get($settings, 'map.longitude'));
        $this->assertSame('<iframe existing></iframe>', data_get($settings, 'map.iframe'));
        $this->assertFalse(Schema::hasColumn('app_settings', 'contact_emails'));
        $this->assertFalse(Schema::hasColumn('app_settings', 'iframe_map'));
    }
}
