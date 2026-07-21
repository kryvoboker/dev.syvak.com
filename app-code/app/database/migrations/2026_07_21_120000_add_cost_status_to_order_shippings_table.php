<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('order_shippings', function (Blueprint $table): void {
            $table->boolean('is_cost_enabled')->default(true)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('order_shippings', function (Blueprint $table): void {
            $table->dropColumn('is_cost_enabled');
        });
    }
};
