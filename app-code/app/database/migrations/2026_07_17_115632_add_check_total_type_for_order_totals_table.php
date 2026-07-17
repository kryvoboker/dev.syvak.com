<?php

use App\Enums\Order\TotalTypesEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_prefix = DB::connection()->getTablePrefix();
        $db_pdo       = DB::getPdo();
        $total_types  = TotalTypesEnum::values() |> (fn(array $values): string => implode(
                ', ',
                array_map(
                    static fn(string $value): string => $db_pdo->quote($value),
                    $values
                )
            ));

        $sql = "
            ALTER TABLE `{$table_prefix}order_totals`
                ADD CONSTRAINT `{$table_prefix}order_totals_total_type_check`
                CHECK (`total_type` IN ($total_types))
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
            ALTER TABLE `{$table_prefix}order_totals`
            DROP CHECK `{$table_prefix}order_totals_total_type_check`
        ");
    }
};
