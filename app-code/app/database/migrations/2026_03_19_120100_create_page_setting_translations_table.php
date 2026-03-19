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
        Schema::create('page_setting_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('page_setting_id')
                ->constrained('page_settings')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('language_id')
                ->constrained('languages')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->json('content')->nullable();

            $table->timestamps();

            $table->unique(['page_setting_id', 'language_id'], 'page_setting_translations_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_setting_translations');
    }
};
