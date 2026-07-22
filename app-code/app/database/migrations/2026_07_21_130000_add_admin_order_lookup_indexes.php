<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index('added_at', 'ord_added_at_idx');
            $table->index(['deleted_at', 'added_at'], 'ord_deleted_added_idx');
        });

        Schema::table('order_payments', function (Blueprint $table): void {
            $table->index(['payment_status_id', 'order_id'], 'ord_pay_status_order_idx');
        });

        Schema::table('order_histories', function (Blueprint $table): void {
            $table->index(['order_id', 'created_at', 'id'], 'ord_hist_order_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_histories', function (Blueprint $table): void {
            $table->dropIndex('ord_hist_order_date_idx');
        });

        Schema::table('order_payments', function (Blueprint $table): void {
            $table->dropIndex('ord_pay_status_order_idx');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('ord_added_at_idx');
            $table->dropIndex('ord_deleted_added_idx');
        });
    }
};
