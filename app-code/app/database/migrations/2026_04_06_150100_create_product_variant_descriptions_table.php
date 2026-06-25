<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_descriptions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('language_id')
                ->nullable()
                ->constrained('languages')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            $table->timestamps();

            $table->unique(['product_variant_id', 'language_id'], 'product_variant_desc_variant_lang_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_descriptions');
    }
};
