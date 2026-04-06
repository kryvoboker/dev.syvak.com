<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_attribute_values', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('attribute_id')
                ->nullable()
                ->constrained('attributes')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('language_id')
                ->nullable()
                ->constrained('languages')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('value_string', 3000)->nullable();

            $table->timestamps();

            $table->index(['product_variant_id', 'attribute_id'], 'product_variant_attribute_values_pvi_ai_index');
            $table->index(['attribute_id', 'language_id'], 'product_variant_attribute_values_ai_li_index');
            $table->index('value_string', 'product_variant_attribute_values_value_string_index');
            $table->unique(['product_variant_id', 'attribute_id', 'language_id'], 'product_variant_attr_value_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_values');
    }
};
