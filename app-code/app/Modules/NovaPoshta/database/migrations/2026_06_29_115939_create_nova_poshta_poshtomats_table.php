<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nova_poshta_poshtomats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nova_poshta_city_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('ref')
                ->index()
                ->nullable();
            $table->string('city_ref')
                ->index()
                ->nullable();
            $table->string('description', 500)->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->tinyText('schedule')->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->string('city_description', 500)->nullable();
            $table->unsignedBigInteger('site_key')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_poshta_poshtomats');
    }
};
