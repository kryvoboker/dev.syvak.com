<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('default_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('default_category_id')
                ->nullable()
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('model')->unique()->nullable();
            $table->string('sku')->index()->nullable();
            $table->string('ean')->unique()->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum')->default(1);
            $table->string('image', 3000)->nullable();
            $table->decimal('price', 15, 4)->default(0);
            $table->unsignedInteger('viewed')->default(0);
            $table->boolean('is_active')->default(false);
            $table->dateTime('date_available')->nullable()->useCurrent();
            $table->dateTime('date_added')->nullable()->useCurrent();
            $table->json('size_guide_data')->nullable();
            $table->json('composition_and_care_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
