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
        Schema::create('page_setting_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('page_setting_id')
                ->constrained('page_settings')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('type', 20)->nullable(false);
            $table->string('code', 120)->nullable(false);
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('is_enabled')->default(true)->nullable(false);
            $table->unsignedInteger('sort_order')->default(0)->nullable(false);
            $table->json('get')->nullable(false);
            $table->json('config')->nullable();

            $table->timestamps();

            $table->unique(['page_setting_id', 'type', 'code'], 'page_setting_items_unique_idx');
            $table->index(['page_setting_id', 'type', 'is_enabled'], 'page_setting_items_scope_idx');
            $table->index(['source_type', 'source_id'], 'page_setting_items_source_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_setting_items');
    }
};
