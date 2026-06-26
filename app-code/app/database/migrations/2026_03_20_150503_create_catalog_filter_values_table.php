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
        Schema::create('catalog_filter_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_filter_group_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('code', 120)->nullable(false);
            $table->string('value_type', 40)->nullable(false);
            $table->string('value_string', 255)->nullable();
            $table->decimal('value_number', 15, 4)->nullable();
            $table->decimal('range_from', 15, 4)->nullable();
            $table->decimal('range_to', 15, 4)->nullable();
            $table->boolean('is_enabled')->default(true)->nullable(false);
            $table->unsignedInteger('sort_order')->default(0)->nullable(false);
            $table->unsignedInteger('products_count_cached')->default(0)->nullable(false);
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['catalog_filter_group_id', 'code'], 'catalog_filter_values_group_code_unique_idx');
            $table->index(
                ['catalog_filter_group_id', 'is_enabled', 'sort_order'],
                'catalog_filter_values_group_enabled_sort_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_filter_values');
    }
};
