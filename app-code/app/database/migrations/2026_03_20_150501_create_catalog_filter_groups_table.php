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
        Schema::create('catalog_filter_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_filter_set_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('code', 120)->nullable(false);
            $table->string('source_type', 40)->nullable(false);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('is_enabled')->default(true)->nullable(false);
            $table->unsignedInteger('sort_order')->default(0)->nullable(false);
            $table->string('get_key', 120)->nullable();
            $table->json('config')->nullable();

            $table->timestamps();

            $table->unique(['catalog_filter_set_id', 'code'], 'catalog_filter_groups_set_code_unique_idx');
            $table->index(
                ['catalog_filter_set_id', 'is_enabled', 'sort_order'],
                'catalog_filter_groups_set_enabled_sort_idx',
            );
            $table->index(['source_type', 'source_id'], 'catalog_filter_groups_source_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_filter_groups');
    }
};
