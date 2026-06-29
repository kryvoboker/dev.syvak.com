<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nova_poshta_cities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nova_poshta_region_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('ref')
                ->index()
                ->nullable();
            $table->string('region')
                ->index()
                ->nullable();
            $table->string('description', 500)->nullable();
            $table->string('city_name')
                ->index()
                ->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('region_description', 500)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_poshta_cities');
    }
};
