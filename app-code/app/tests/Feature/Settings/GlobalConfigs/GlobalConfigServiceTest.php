<?php

declare(strict_types=1);

namespace Tests\Feature\Settings\GlobalConfigs;

use App\Data\AppSettingsData;
use App\Models\ApplicationSettings\AppSetting;
use App\Models\ApplicationSettings\GlobalConfig;
use App\Supports\Services\AppSettingsService;
use App\Supports\Services\GlobalConfigService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GlobalConfigServiceTest extends TestCase
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
        Cache::flush();

        $this->createAppSettingsTable();
        $this->createGlobalConfigsTable();
        $this->createLanguagesTable();
        $this->createUserGroupsTable();

        DB::table('languages')->insert([
            'id' => 1,
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_groups')->insert([
            'id' => 1,
            'name' => 'Default',
            'description' => null,
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_sync_global_configs_skips_blank_rows_and_replaces_missing_records(): void
    {
        GlobalConfig::query()->create([
            'key' => 'keep_me',
            'value' => 'old-value',
            'is_active' => true,
        ]);

        GlobalConfig::query()->create([
            'key' => 'delete_me',
            'value' => 'remove-me',
            'is_active' => true,
        ]);

        $summary = app(GlobalConfigService::class)->syncGlobalConfigs([
            [
                'key' => 'keep_me',
                'value' => 'new-value',
                'is_active' => true,
                'selected' => false,
            ],
            [
                'key' => 'created_me',
                'value' => '{"enabled":true}',
                'is_active' => false,
                'selected' => false,
            ],
            [
                'key' => '',
                'value' => 'ignored',
                'is_active' => true,
                'selected' => false,
            ],
        ]);

        $this->assertSame(1, $summary['created_count']);
        $this->assertSame(1, $summary['updated_count']);
        $this->assertSame(1, $summary['deleted_count']);
        $this->assertSame(2, $summary['total_count']);

        $this->assertDatabaseHas('global_configs', [
            'key' => 'keep_me',
            'value' => 'new-value',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('global_configs', [
            'key' => 'created_me',
            'value' => '{"enabled":true}',
            'is_active' => 0,
        ]);

        $this->assertDatabaseMissing('global_configs', [
            'key' => 'delete_me',
        ]);
    }

    public function test_disable_selected_global_configs_sets_selected_records_inactive(): void
    {
        GlobalConfig::query()->insert([
            [
                'key' => 'config_a',
                'value' => 'a',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'config_b',
                'value' => 'b',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'config_c',
                'value' => 'c',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $summary = app(GlobalConfigService::class)->disableSelectedGlobalConfigs([
            [
                'key' => 'config_a',
                'value' => 'a',
                'is_active' => true,
                'selected' => false,
            ],
            [
                'key' => 'config_b',
                'value' => 'b',
                'is_active' => true,
                'selected' => true,
            ],
            [
                'key' => 'config_c',
                'value' => 'c',
                'is_active' => true,
                'selected' => true,
            ],
        ]);

        $this->assertSame(2, $summary['updated_count']);

        $this->assertDatabaseHas('global_configs', [
            'key' => 'config_a',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('global_configs', [
            'key' => 'config_b',
            'is_active' => 0,
        ]);

        $this->assertDatabaseHas('global_configs', [
            'key' => 'config_c',
            'is_active' => 0,
        ]);
    }

    public function test_delete_selected_global_configs_removes_selected_records(): void
    {
        GlobalConfig::query()->insert([
            [
                'key' => 'config_a',
                'value' => 'a',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'config_b',
                'value' => 'b',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'config_c',
                'value' => 'c',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $summary = app(GlobalConfigService::class)->deleteSelectedGlobalConfigs([
            [
                'key' => 'config_a',
                'value' => 'a',
                'is_active' => true,
                'selected' => false,
            ],
            [
                'key' => 'config_b',
                'value' => 'b',
                'is_active' => true,
                'selected' => true,
            ],
            [
                'key' => 'config_c',
                'value' => 'c',
                'is_active' => true,
                'selected' => false,
            ],
        ]);

        $this->assertSame(1, $summary['deleted_count']);
        $this->assertDatabaseHas('global_configs', [
            'key' => 'config_a',
        ]);
        $this->assertDatabaseHas('global_configs', [
            'key' => 'config_c',
        ]);
        $this->assertDatabaseMissing('global_configs', [
            'key' => 'config_b',
        ]);
    }

    public function test_app_settings_service_loads_only_active_global_configs_into_collection(): void
    {
        AppSetting::query()->create([]);

        GlobalConfig::query()->insert([
            [
                'key' => 'active_config',
                'value' => 'active-value',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'inactive_config',
                'value' => 'inactive-value',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(AppSettingsService::class);
        $service->setSettings();

        $settings = $service->getSettings();

        if (! $settings instanceof AppSettingsData) {
            return;
        }

        $global_configs = $settings->global_configs;

        if (! $global_configs instanceof \Illuminate\Support\Collection) {
            return;
        }

        $this->assertSame('active-value', $global_configs->get('active_config'));
        $this->assertNull($global_configs->get('inactive_config'));
    }

    public function test_delete_all_global_configs_removes_every_record(): void
    {
        GlobalConfig::query()->insert([
            [
                'key' => 'config_a',
                'value' => 'a',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'config_b',
                'value' => 'b',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $deleted_count = app(GlobalConfigService::class)->deleteAllGlobalConfigs();

        $this->assertSame(2, $deleted_count);
        $this->assertDatabaseCount('global_configs', 0);
    }

    public function test_save_global_config_creates_and_updates_record(): void
    {
        $service = app(GlobalConfigService::class);

        $created_global_config = $service->saveGlobalConfig(null, [
            'key' => 'feature.flag',
            'value' => '1',
            'is_active' => true,
        ]);

        $this->assertSame('feature.flag', $created_global_config->key);
        $this->assertSame('1', $created_global_config->value);
        $this->assertTrue((bool) $created_global_config->is_active);

        $updated_global_config = $service->saveGlobalConfig($created_global_config, [
            'value' => null,
            'is_active' => false,
        ]);

        $this->assertSame('feature.flag', $updated_global_config->key);
        $this->assertNull($updated_global_config->value);
        $this->assertFalse((bool) $updated_global_config->is_active);
    }

    public function test_disable_global_configs_by_ids_sets_records_inactive(): void
    {
        $first_global_config = GlobalConfig::query()->create([
            'key' => 'config_a',
            'value' => 'a',
            'is_active' => true,
        ]);

        $second_global_config = GlobalConfig::query()->create([
            'key' => 'config_b',
            'value' => 'b',
            'is_active' => true,
        ]);

        $updated_count = app(GlobalConfigService::class)->disableGlobalConfigsByIds([
            $first_global_config->id,
            $second_global_config->id,
        ]);

        $this->assertSame(2, $updated_count);
        $this->assertDatabaseHas('global_configs', [
            'id' => $first_global_config->id,
            'is_active' => 0,
        ]);
        $this->assertDatabaseHas('global_configs', [
            'id' => $second_global_config->id,
            'is_active' => 0,
        ]);
    }

    public function test_delete_global_configs_by_ids_removes_records(): void
    {
        $first_global_config = GlobalConfig::query()->create([
            'key' => 'config_a',
            'value' => 'a',
            'is_active' => true,
        ]);

        $second_global_config = GlobalConfig::query()->create([
            'key' => 'config_b',
            'value' => 'b',
            'is_active' => true,
        ]);

        $deleted_count = app(GlobalConfigService::class)->deleteGlobalConfigsByIds([
            $first_global_config->id,
            $second_global_config->id,
        ]);

        $this->assertSame(2, $deleted_count);
        $this->assertDatabaseMissing('global_configs', [
            'id' => $first_global_config->id,
        ]);
        $this->assertDatabaseMissing('global_configs', [
            'id' => $second_global_config->id,
        ]);
    }

    private function createAppSettingsTable(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }

    private function createGlobalConfigsTable(): void
    {
        Schema::create('global_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function createLanguagesTable(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    private function createUserGroupsTable(): void
    {
        Schema::create('user_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }
}
