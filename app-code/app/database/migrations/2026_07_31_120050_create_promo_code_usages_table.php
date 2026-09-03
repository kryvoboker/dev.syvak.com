<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('promo_code_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promo_code_id')->constrained('promo_codes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('discount_type', 20);
            $table->string('promo_type', 20);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_group_id')->nullable()->constrained('user_groups')->nullOnDelete();
            $table->string('consumer_key', 255)->nullable();
            $table->dateTime('used_at');
            $table->timestamps();

            $table->unique(['promo_code_id', 'order_id']);
            $table->index(['promo_code_id', 'user_id']);
            $table->index(['promo_code_id', 'user_group_id']);
            $table->index(['promo_code_id', 'consumer_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_usages');
    }
};
