<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\UkrPoshta\Models\UkrPoshtaCity;
use Modules\UkrPoshta\Models\UkrPoshtaDistrict;
use Modules\UkrPoshta\Models\UkrPoshtaPostOffice;
use Modules\UkrPoshta\Models\UkrPoshtaRegion;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createUkrPoshtaTables();
        $this->app['request']->setLaravelSession($this->app['session']->driver());
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists((new UkrPoshtaPostOffice())->getTable());
        Schema::dropIfExists((new UkrPoshtaCity())->getTable());
        Schema::dropIfExists((new UkrPoshtaDistrict())->getTable());
        Schema::dropIfExists((new UkrPoshtaRegion())->getTable());
        Schema::dropIfExists('global_configs');

        parent::tearDown();
    }

    protected function createUkrPoshtaTables(): void
    {
        Schema::dropIfExists((new UkrPoshtaPostOffice())->getTable());
        Schema::dropIfExists((new UkrPoshtaCity())->getTable());
        Schema::dropIfExists((new UkrPoshtaDistrict())->getTable());
        Schema::dropIfExists((new UkrPoshtaRegion())->getTable());
        Schema::dropIfExists('global_configs');

        Schema::create('global_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create((new UkrPoshtaRegion())->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('region_id')->unique()->nullable();
            $table->string('region_ua', 500)->nullable();
            $table->timestamps();
        });

        Schema::create((new UkrPoshtaDistrict())->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ukr_poshta_region_id')
                ->constrained('ukr_poshta_regions', 'region_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('district_id')->unique()->nullable();
            $table->string('region_ua', 500)->nullable();
            $table->string('district_ua', 500)->nullable();
            $table->timestamps();
        });

        Schema::create((new UkrPoshtaCity())->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ukr_poshta_region_id')
                ->constrained('ukr_poshta_regions', 'region_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('ukr_poshta_district_id')
                ->constrained('ukr_poshta_districts', 'district_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('city_id')->unique()->nullable();
            $table->string('description', 500)->nullable();
            $table->string('city_ua', 500)->index()->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('region_ua', 500)->nullable();
            $table->string('district_ua', 500)->nullable();
            $table->timestamps();
        });

        Schema::create((new UkrPoshtaPostOffice())->getTable(), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poregion_id')
                ->constrained('ukr_poshta_regions', 'region_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('podistrict_id')
                ->constrained('ukr_poshta_districts', 'district_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('pdcity_id')
                ->constrained('ukr_poshta_cities', 'city_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('description', 500)->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->integer('lock_code')->index()->nullable();
            $table->unsignedInteger('postcode')->index()->nullable();
            $table->string('region_ua', 500)->nullable();
            $table->string('district_ua', 500)->nullable();
            $table->string('postreet_id')->index()->nullable();
            $table->timestamps();
        });
    }
}
