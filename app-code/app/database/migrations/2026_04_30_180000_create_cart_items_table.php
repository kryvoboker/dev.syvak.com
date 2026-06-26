<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->string('session_id', 255)->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('cart_mode', 30)->default('regular');
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->json('chosen_attributes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
            $table->index(['session_id', 'updated_at']);
            $table->index(['cart_mode', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
