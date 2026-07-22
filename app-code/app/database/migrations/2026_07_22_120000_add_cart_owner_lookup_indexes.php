<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'cart_mode', 'updated_at'],
                'crt_usr_mode_upd_idx',
            );
            $table->index(
                ['session_id', 'cart_mode', 'updated_at'],
                'crt_ses_mode_upd_idx',
            );
            $table->index(
                ['user_id', 'cart_mode', 'product_variant_id'],
                'crt_usr_mode_var_idx',
            );
            $table->index(
                ['session_id', 'cart_mode', 'product_variant_id'],
                'crt_ses_mode_var_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->dropIndex('crt_usr_mode_upd_idx');
            $table->dropIndex('crt_ses_mode_upd_idx');
            $table->dropIndex('crt_usr_mode_var_idx');
            $table->dropIndex('crt_ses_mode_var_idx');
        });
    }
};
