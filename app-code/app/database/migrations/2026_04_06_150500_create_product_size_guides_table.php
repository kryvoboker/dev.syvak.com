<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('product_size_guides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('languages')->cascadeOnUpdate()->nullOnDelete();
            $table->string('short_title')->nullable();
            $table->string('short_description', 1000)->nullable();
            $table->text('table_rows')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->string('full_description_title')->nullable();
            $table->text('full_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_size_guides');
    }
};
