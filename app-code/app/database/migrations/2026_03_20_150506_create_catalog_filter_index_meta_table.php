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
        Schema::create('catalog_filter_index_meta', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_filter_set_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('index_version')->default(1)->nullable(false);
            $table->unsignedBigInteger('active_index_version')->default(1)->nullable(false);
            $table->unsignedBigInteger('building_index_version')->nullable();
            $table->string('rebuild_lock_key', 120)->nullable();
            $table->timestamp('rebuild_lock_acquired_at')->nullable();
            $table->timestamp('last_full_rebuild_at')->nullable();
            $table->timestamp('last_incremental_sync_at')->nullable();
            $table->string('last_status', 20)->default('ok')->nullable(false);
            $table->text('last_error')->nullable();
            $table->unsignedTinyInteger('last_progress_percent')->nullable();
            $table->string('last_run_mode', 20)->nullable();
            $table->unsignedInteger('items_total')->default(0)->nullable(false);
            $table->unsignedInteger('values_total')->default(0)->nullable(false);
            $table->unsignedBigInteger('index_rows_total')->default(0)->nullable(false);

            $table->timestamps();

            $table->unique(['catalog_filter_set_id'], 'catalog_filter_index_meta_set_unique_idx');
            $table->index(
                ['catalog_filter_set_id', 'active_index_version'],
                'catalog_filter_index_meta_set_active_version_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_filter_index_meta');
    }
};
