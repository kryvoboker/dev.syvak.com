<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('promo_code_discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promo_code_id')->constrained('promo_codes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('value', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['promo_code_id', 'currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_discounts');
    }
};
