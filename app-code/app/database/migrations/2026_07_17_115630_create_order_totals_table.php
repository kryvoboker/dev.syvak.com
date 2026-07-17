<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_totals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('total_type')
                ->nullable(false)
                ->comment('For check is total is DISCOUNT (discount) or SHIPPING (shipping) or PROMO CODE (promo_code) or SUBTOTAL (sub_total) or TOTAL (total) or OTHER');

            $table->string('name')
                ->nullable(false)
                ->comment('The name of the total type');

            $table->decimal('value', 15, 4)->default(0.0000);
            $table->unsignedInteger('sort_order')->default(1);

            $table->timestamps();

            $table->index(['order_id', 'total_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_totals');
    }
};
