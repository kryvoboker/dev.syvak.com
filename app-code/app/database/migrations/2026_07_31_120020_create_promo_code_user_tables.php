<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('promo_code_user', function (Blueprint $table): void {
            $table->foreignId('promo_code_id')->constrained('promo_codes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->primary(['promo_code_id', 'user_id']);
        });

        Schema::create('promo_code_user_group', function (Blueprint $table): void {
            $table->foreignId('promo_code_id')->constrained('promo_codes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_group_id')->constrained('user_groups')->cascadeOnUpdate()->cascadeOnDelete();
            $table->primary(['promo_code_id', 'user_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_user_group');
        Schema::dropIfExists('promo_code_user');
    }
};
