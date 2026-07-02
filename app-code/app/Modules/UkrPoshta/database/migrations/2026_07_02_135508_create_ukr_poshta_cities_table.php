<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ukr_poshta_cities', function (Blueprint $table): void {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('ukr_poshta_cities');
    }
};
