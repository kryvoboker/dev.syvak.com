<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Checkout\CheckoutBranchSearchService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\NovaPoshta\Models\NovaPoshtaPoshtomat;
use Modules\NovaPoshta\Models\NovaPoshtaPostOffice;
use Modules\UkrPoshta\Models\UkrPoshtaPostOffice;
use Tests\TestCase;

final class CheckoutBranchSearchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_it_loads_all_nova_poshta_post_offices_for_selected_city(): void
    {
        NovaPoshtaPostOffice::query()->create([
            'ref' => 'office-2',
            'city_ref' => 'city-1',
            'description' => 'Second office',
            'city_description' => 'Kyiv',
            'latitude' => '50.5000',
            'longitude' => '30.5000',
            'number' => 2,
            'site_key' => 11,
            'schedule' => null,
        ]);

        NovaPoshtaPostOffice::query()->create([
            'ref' => 'office-1',
            'city_ref' => 'city-1',
            'description' => 'First office',
            'city_description' => 'Kyiv',
            'latitude' => '50.4000',
            'longitude' => '30.4000',
            'number' => 1,
            'site_key' => 10,
            'schedule' => null,
        ]);

        NovaPoshtaPostOffice::query()->create([
            'ref' => 'office-3',
            'city_ref' => 'city-2',
            'description' => 'Foreign office',
            'city_description' => 'Lviv',
            'latitude' => '49.8000',
            'longitude' => '24.0000',
            'number' => 3,
            'site_key' => 12,
            'schedule' => null,
        ]);

        $result = app(CheckoutBranchSearchService::class)->loadBranches('nova_poshta', [
            'nova_poshta_city_id' => 'city-1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['items']);
        $this->assertSame('First office', $result['items'][0]['description']);
        $this->assertSame('Second office', $result['items'][1]['description']);
    }

    public function test_it_loads_all_nova_poshta_poshtomats_for_selected_city(): void
    {
        NovaPoshtaPoshtomat::query()->create([
            'ref' => 'poshtomat-2',
            'city_ref' => 'city-1',
            'description' => 'Poshtomat B',
            'city_description' => 'Kyiv',
            'latitude' => '50.6000',
            'longitude' => '30.6000',
            'number' => 20,
            'site_key' => 21,
            'schedule' => null,
        ]);

        NovaPoshtaPoshtomat::query()->create([
            'ref' => 'poshtomat-1',
            'city_ref' => 'city-1',
            'description' => 'Poshtomat A',
            'city_description' => 'Kyiv',
            'latitude' => '50.7000',
            'longitude' => '30.7000',
            'number' => 10,
            'site_key' => 20,
            'schedule' => null,
        ]);

        $result = app(CheckoutBranchSearchService::class)->loadBranches('nova_poshta_poshtomat', [
            'nova_poshta_city_id' => 'city-1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['items']);
        $this->assertSame('Poshtomat A', $result['items'][0]['description']);
        $this->assertSame('Poshtomat B', $result['items'][1]['description']);
    }

    public function test_it_loads_all_ukr_poshta_post_offices_for_selected_city(): void
    {
        UkrPoshtaPostOffice::query()->create([
            'poregion_id' => 1,
            'podistrict_id' => 10,
            'pdcity_id' => 100,
            'description' => 'Branch B',
            'latitude' => '50.3000',
            'longitude' => '30.3000',
            'lock_code' => 0,
            'postcode' => 2,
            'region_ua' => 'Region',
            'district_ua' => 'District',
            'postreet_id' => 5,
        ]);

        UkrPoshtaPostOffice::query()->create([
            'poregion_id' => 1,
            'podistrict_id' => 10,
            'pdcity_id' => 100,
            'description' => 'Branch A',
            'latitude' => '50.2000',
            'longitude' => '30.2000',
            'lock_code' => 0,
            'postcode' => 1,
            'region_ua' => 'Region',
            'district_ua' => 'District',
            'postreet_id' => 4,
        ]);

        UkrPoshtaPostOffice::query()->create([
            'poregion_id' => 2,
            'podistrict_id' => 11,
            'pdcity_id' => 101,
            'description' => 'Other city branch',
            'latitude' => '49.0000',
            'longitude' => '24.0000',
            'lock_code' => 0,
            'postcode' => 3,
            'region_ua' => 'Region 2',
            'district_ua' => 'District 2',
            'postreet_id' => 6,
        ]);

        $result = app(CheckoutBranchSearchService::class)->loadBranches('ukr_poshta', [
            'ukr_poshta_city_id' => 100,
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['items']);
        $this->assertSame('Branch A', $result['items'][0]['description']);
        $this->assertSame('Branch B', $result['items'][1]['description']);
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('module_definitions');
        Schema::dropIfExists('nova_poshta_post_offices');
        Schema::dropIfExists('nova_poshta_poshtomats');
        Schema::dropIfExists('ukr_poshta_post_offices');

        Schema::create('module_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('nwidart_name')->unique();
            $table->string('module_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_installed')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_enabled_in_filesystem')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings_schema')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('nova_poshta_post_offices', function (Blueprint $table): void {
            $table->id();
            $table->string('ref');
            $table->string('city_ref');
            $table->string('description');
            $table->string('city_description')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->text('schedule')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedInteger('site_key')->nullable();
            $table->timestamps();
        });

        Schema::create('nova_poshta_poshtomats', function (Blueprint $table): void {
            $table->id();
            $table->string('ref');
            $table->string('city_ref');
            $table->string('description');
            $table->string('city_description')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->text('schedule')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedInteger('site_key')->nullable();
            $table->timestamps();
        });

        Schema::create('ukr_poshta_post_offices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('poregion_id');
            $table->unsignedInteger('podistrict_id');
            $table->unsignedInteger('pdcity_id');
            $table->string('description');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->unsignedInteger('lock_code')->default(0);
            $table->unsignedInteger('postcode')->nullable();
            $table->string('region_ua')->nullable();
            $table->string('district_ua')->nullable();
            $table->unsignedInteger('postreet_id')->nullable();
            $table->timestamps();
        });

        $this->seedModuleDefinition('NovaPoshta');
        $this->seedModuleDefinition('UkrPoshta');
    }

    private function seedModuleDefinition(string $module_name): void
    {
        \App\Models\Modules\ModuleDefinition::query()->create([
            'name' => $module_name,
            'slug' => strtolower($module_name),
            'nwidart_name' => $module_name,
            'module_path' => null,
            'description' => null,
            'is_installed' => true,
            'is_enabled' => true,
            'is_enabled_in_filesystem' => true,
            'sort_order' => 0,
            'settings_schema' => [],
            'meta' => [
                'admin' => [
                    'can_create_instances' => false,
                ],
            ],
        ]);
    }
}
