<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('promo_code_error_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promo_code_id')->constrained('promo_codes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('expired_message')->nullable();
            $table->text('minimum_order_message')->nullable();
            $table->text('usage_limit_message')->nullable();
            $table->timestamps();

            $table->unique(['promo_code_id', 'language_id'], 'promo_code_error_translations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_error_translations');
    }
};
