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
        Schema::create('catalog_filter_value_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_filter_value_id')
                ->constrained(indexName: 'catalog_filter_value_translations_c_f_v_id_foreign')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('language_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('label', 255)->nullable();
            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(
                ['catalog_filter_value_id', 'language_id'],
                'catalog_filter_value_translations_unique_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_filter_value_translations');
    }
};
