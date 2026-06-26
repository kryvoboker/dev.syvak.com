<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('catalog_filter_sets', function (Blueprint $table) {
            $table->id();

            $table->string('code', 120)->nullable(false);
            $table->string('context_type', 40)->nullable(false);
            $table->json('context_types')->nullable();
            $table->boolean('is_enabled')->default(true)->nullable(false);
            $table->boolean('is_price_filter_enabled')->default(true)->nullable(false);
            $table->boolean('is_attribute_filtering_enabled')->default(true)->nullable(false);
            $table->string('price_source_mode', 40)->nullable(false);
            $table->string('facet_strategy', 40)->nullable(false);
            $table->string('discount_only_policy', 60)->nullable(false);
            $table->unsignedInteger('min_stock_quantity')->default(1)->nullable(false);
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->unique(['code'], 'catalog_filter_sets_code_unique_idx');
            $table->index(['context_type', 'is_enabled'], 'catalog_filter_sets_context_enabled_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_filter_sets');
    }
};
