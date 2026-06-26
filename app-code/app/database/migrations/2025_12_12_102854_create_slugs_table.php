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
        Schema::create('slugs', function (Blueprint $table) {
            $table->id();

            $table->morphs('sluggable'); // sluggable_id, sluggable_type

            $table->foreignId('language_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('slug', 500)
                ->unique()
                ->comment('SEO friendly URL');

            $table->timestamps();

            $table->unique(['sluggable_type', 'sluggable_id', 'language_id']);
            $table->unique(['language_id', 'slug']);
            $table->index(['slug', 'language_id']);
            $table->index(['sluggable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slugs');
    }
};
