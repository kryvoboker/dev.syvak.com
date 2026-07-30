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
        Schema::create('order_shippings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->unique()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('method')->nullable();
            $table->string('code')->nullable();
            $table->string('city')->nullable();
            $table->string('city_id')
                ->index()
                ->nullable();

            $table->string('address')
                ->nullable()
                ->comment('For courier delivery');

            $table->string('delivery_point')
                ->nullable()
                ->comment('For post office or poshtomat delivery');

            $table->string('delivery_point_id')
                ->index()
                ->nullable();

            $table->string('postcode')->nullable();
            $table->json('provider_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shippings');
    }
};
