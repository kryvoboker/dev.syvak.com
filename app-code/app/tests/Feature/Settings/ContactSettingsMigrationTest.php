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

    public function test_legacy_contact_data_migration_is_not_required_after_schema_reorganization(): void
    {
        $this->assertFileDoesNotExist(
            database_path('migrations/2026_08_06_174541_move_contact_data_to_contacts_page_settings.php'),
        );
    }
}
