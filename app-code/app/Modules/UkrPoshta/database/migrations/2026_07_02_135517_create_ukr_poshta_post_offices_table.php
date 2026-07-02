<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ukr_poshta_post_offices', function (Blueprint $table): void {
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
            $table->integer('lock_code')->index()->nullable()->comment('0 - it means that the office is work');
            $table->unsignedInteger('postcode')->index()->nullable();
            $table->string('region_ua', 500)->nullable();
            $table->string('district_ua', 500)->nullable();
            $table->string('postreet_id')->index()->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ukr_poshta_post_offices');
    }
};
