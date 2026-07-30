<?php

declare(strict_types=1);

use App\Enums\Cart\CartModeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_prefix = DB::connection()->getTablePrefix();
        $db_pdo = DB::getPdo();
        $quoted_cart_types = array_map(
            static fn (string $value): string => $db_pdo->quote($value),
            CartModeEnum::values(),
        );
        $cart_types = implode(', ', $quoted_cart_types);

        $sql = "
            ALTER TABLE `{$table_prefix}orders`
                ADD CONSTRAINT `{$table_prefix}orders_order_type_check`
                CHECK (`order_type` IN ($cart_types))
        ";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table_prefix = DB::connection()->getTablePrefix();

        DB::statement("
            ALTER TABLE `{$table_prefix}orders`
            DROP CHECK `{$table_prefix}orders_order_type_check`
        ");
    }
};
