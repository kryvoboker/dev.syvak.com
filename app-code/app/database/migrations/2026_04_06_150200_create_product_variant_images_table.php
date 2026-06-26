<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('product_variant_images', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('image', 3000)->nullable(false);
            $table->boolean('is_primary')->default(false);
            $table->smallInteger('sort_order')->default(1);

            $table->timestamps();

            $table->index(['product_variant_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_images');
    }
};
