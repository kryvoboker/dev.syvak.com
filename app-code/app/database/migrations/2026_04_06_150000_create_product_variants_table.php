<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('minimum')->default(1);
            $table->decimal('price', 15, 4)->default(0);
            $table->string('image', 3000)->nullable();
            $table->dateTime('date_available')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->json('size_guide_data')->nullable();
            $table->json('composition_and_care_data')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index(['product_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
