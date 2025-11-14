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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();

            $table->string('code', 10)->unique()->nullable(false);
            $table->string('name', 100)->nullable(false);
            $table->string('symbol_left', 10)->nullable();
            $table->string('symbol_right', 10)->nullable();
            $table->smallInteger('decimal_places')->default(0)->nullable(false);
            $table->decimal('exchange_rate', 15, 6)->default(1.000000)->nullable(false);
            $table->boolean('is_active')->default(false)->nullable(false);
            $table->boolean('is_default')->default(false)->nullable(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
