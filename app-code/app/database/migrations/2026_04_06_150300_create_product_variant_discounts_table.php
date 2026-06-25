<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_discounts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_group_id')
                ->nullable()
                ->constrained('user_groups')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('priority')->default(1);
            $table->decimal('price', 15, 4)->default(0);
            $table->dateTime('date_start')->nullable(false);
            $table->dateTime('date_end')->nullable(false);

            $table->timestamps();

            $table->index(['product_variant_id', 'user_group_id'], 'product_variant_discounts_variant_group_idx');
            $table->index(['date_start', 'date_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_discounts');
    }
};
