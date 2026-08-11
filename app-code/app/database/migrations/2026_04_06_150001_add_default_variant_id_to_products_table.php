<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('default_variant_id')
                ->nullable()
                ->constrained('product_variants', 'id')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_histories', function (Blueprint $table): void {
            $table->dropForeign('default_variant_id');
            $table->dropColumn('default_variant_id');
        });
    }
};
