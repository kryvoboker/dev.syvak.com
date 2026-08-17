<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Modules\ModuleDefinition;
use App\Services\Checkout\CheckoutCitySearchService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\NovaPoshta\Models\NovaPoshtaCity;
use Tests\TestCase;

final class CheckoutCitySearchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('module_definitions');
        Schema::dropIfExists('nova_poshta_cities');
        Schema::dropIfExists('nova_poshta_regions');

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

        Schema::create('nova_poshta_regions', function (Blueprint $table): void {
            $table->id();
            $table->string('ref')->nullable();
            $table->string('regions_center')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('nova_poshta_cities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('nova_poshta_region_id')->nullable();
            $table->string('ref')->nullable();
            $table->string('region')->nullable();
            $table->string('description')->nullable();
            $table->string('city_name')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('region_description')->nullable();
            $table->timestamps();
        });

        ModuleDefinition::query()->create([
            'name' => 'NovaPoshta',
            'slug' => 'nova-poshta',
            'nwidart_name' => 'NovaPoshta',
            'is_installed' => true,
            'is_enabled' => true,
            'is_enabled_in_filesystem' => true,
            'meta' => ['admin' => ['can_create_instances' => false]],
        ]);
    }

    public function test_nova_poshta_city_reference_remains_a_string_for_checkout_delivery_methods(): void
    {
        $nova_poshta_city_ref = '4f7b612e-2d42-11e5-80b6-005056887b8d';

        NovaPoshtaCity::query()->create([
            'ref' => $nova_poshta_city_ref,
            'description' => 'Kyiv',
            'city_name' => 'Kyiv',
            'region_description' => 'Київська область',
            'latitude' => '50.4501',
            'longitude' => '30.5234',
        ]);

        $result = app(CheckoutCitySearchService::class)->searchCities('Kyiv');

        $this->assertTrue($result['success'], json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $this->assertSame($nova_poshta_city_ref, $result['cities_data'][0]['nova_poshta_city_id']);
    }
}
