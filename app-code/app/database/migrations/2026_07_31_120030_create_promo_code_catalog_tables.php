<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('promo_code_product', function (Blueprint $table): void {
            $table->foreignId('promo_code_id')->constrained('promo_codes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->primary(['promo_code_id', 'product_id']);
        });

        Schema::create('promo_code_category', function (Blueprint $table): void {
            $table->foreignId('promo_code_id')->constrained('promo_codes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnUpdate()->cascadeOnDelete();
            $table->primary(['promo_code_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_category');
        Schema::dropIfExists('promo_code_product');
    }
};
