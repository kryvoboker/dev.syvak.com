<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('order_promo_code_products', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('promo_code_usage_id')->constrained('promo_code_usages')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_product_id')->constrained('order_products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreignId('product_variant_id')->nullable()
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->boolean('is_eligible')->default(false);
            $table->string('override', 20)->nullable();
            $table->decimal('discount_amount', 15, 4)->default(0.0000);

            $table->timestamps();

            $table->unique(['promo_code_usage_id', 'order_product_id']);
            $table->index(['order_id', 'is_eligible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_promo_code_products');
    }
};
