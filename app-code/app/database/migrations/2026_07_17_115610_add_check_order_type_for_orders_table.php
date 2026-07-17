<?php

use App\Enums\Cart\CartModeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_prefix      = DB::connection()->getTablePrefix();
        $db_pdo            = DB::getPdo();
        $cart_types        = CartModeEnum::values() |> (fn(array $values): string => implode(
                ', ',
                array_map(
                    static fn(string $value): string => $db_pdo->quote($value),
                    $values
                )
            ));

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
